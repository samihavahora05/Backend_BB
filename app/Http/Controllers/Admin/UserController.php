<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

use App\Traits\PaginateQuery;
use App\Notifications\PlatformNotification;

class UserController extends Controller
{
    use PaginateQuery;

    public function index(Request $request)
    {
        $query = User::with('roles')
            ->when($request->role, function ($query, $role) {
                $query->role($role);
            })
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            });

        $paginated = $this->paginateWithMeta(
            $query,
            $request,
            ['first_name', 'last_name', 'email', 'created_at'],
            ['first_name', 'last_name', 'email']
        );

        return response()->json(array_merge(['success' => true], $paginated));
    }

    public function store(Request $request)
    {
        $request->validate([
            'email' => 'required|email|unique:users,email',
            'role'  => 'sometimes|string',
            'password' => 'nullable|min:6',
        ]);

        $role = $request->role ?: 'expert';

        $firstName = $request->first_name ?: (explode(' ', $request->name ?? '')[0] ?? 'User');
        $lastName = $request->last_name ?: (implode(' ', array_slice(explode(' ', $request->name ?? ''), 1)) ?: '');
        $fullName = trim($firstName . ' ' . $lastName) ?: ($request->name ?: 'User');

        $user = User::create([
            'first_name' => $firstName,
            'last_name'  => $lastName,
            'name'       => $fullName,
            'email'      => strtolower(trim($request->email)),
            'phone'      => $request->phone ?? null,
            'password'   => \Illuminate\Support\Facades\Hash::make($request->password ?? 'Password@123'),
            'status'     => 'active',
        ]);

        try {
            $roleObj = \Spatie\Permission\Models\Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
            $user->assignRole($roleObj);
        } catch (\Throwable $e) {}

        // Auto-create basic profile based on role so they appear in public listings immediately
        if ($role === 'expert') {
            $photoUrl = null;
            if ($request->hasFile('avatar')) {
                $path = $request->file('avatar')->store('avatars', 'public');
                $photoUrl = '/storage/' . $path;
            } elseif ($request->hasFile('profile_photo')) {
                $path = $request->file('profile_photo')->store('avatars', 'public');
                $photoUrl = '/storage/' . $path;
            } else {
                $avatarStr = $request->input('avatar') ?: $request->input('profile_photo');
                if ($avatarStr && strpos($avatarStr, 'data:image') === 0) {
                    $imageParts = explode(';base64,', $avatarStr);
                    if (count($imageParts) === 2) {
                        $imageType = explode('/', $imageParts[0])[1] ?? 'png';
                        if (str_contains($imageType, ';')) $imageType = explode(';', $imageType)[0];
                        $imageDecoded = base64_decode($imageParts[1]);
                        $fileName = 'avatars/' . uniqid() . '.' . $imageType;
                        \Illuminate\Support\Facades\Storage::disk('public')->put($fileName, $imageDecoded);
                        $photoUrl = '/storage/' . $fileName;
                    }
                } elseif ($avatarStr && (str_starts_with($avatarStr, 'http') || str_starts_with($avatarStr, '/'))) {
                    $photoUrl = $avatarStr;
                }
            }

            \App\Models\ExpertProfile::create([
                'user_id'        => $user->id,
                'designation'    => $request->designation ?: 'Expert',
                'company'        => $request->company ?: 'Independent',
                'specialization' => $request->specialization ?: 'Career & Technical Mentorship',
                'hourly_rate'    => !empty($request->hourly_rate) ? (float)$request->hourly_rate : 1500.0,
                'profile_photo'  => $photoUrl,
                'is_available'   => true,
                'is_verified'    => true,
                'approval_status'=> 'approved',
                'average_rating' => 5.0,
                'total_reviews'  => 0,
            ]);
            \Illuminate\Support\Facades\Cache::flush();
        } elseif ($role === 'student') {
            \App\Models\StudentProfile::create([
                'user_id' => $user->id,
            ]);
        } elseif ($role === 'company') {
            \App\Models\CompanyProfile::create([
                'user_id' => $user->id,
                'company_name' => 'Pending Name'
            ]);
        }

        return response()->json(['success' => true, 'data' => $user->load('roles')], 201);
    }

    public function show(string $id)
    {
        $user = User::with('roles')->findOrFail($id);
        
        // Load the specific profile based on the role
        $role = $user->roles()->first()?->name;
        $profileRelation = match($role) {
            'student' => 'studentProfile',
            'company' => 'companyProfile',
            'college' => 'collegeProfile',
            'expert' => 'expertProfile',
            default => null
        };

        if ($profileRelation) {
            $user->load($profileRelation);
        }

        return response()->json($user);
    }

    public function update(Request $request, string $id)
    {
        $user = User::findOrFail($id);
        
        if ($request->filled('first_name') || $request->filled('last_name')) {
            $user->first_name = $request->first_name ?: $user->first_name;
            $user->last_name = $request->last_name ?: $user->last_name;
            $user->name = trim($user->first_name . ' ' . $user->last_name);
        } elseif ($request->filled('name')) {
            $user->name = $request->name;
        }

        if ($request->filled('phone')) {
            $user->phone = $request->phone;
        }

        if ($request->filled('email')) {
            $user->email = strtolower(trim($request->email));
        }

        $user->save();

        if ($request->filled('role')) {
            $user->syncRoles([$request->role]);
        }

        // Handle expert profile updates if present
        $profile = \App\Models\ExpertProfile::where('user_id', $user->id)->first();
        if ($profile) {
            $photoUrl = null;
            if ($request->hasFile('avatar')) {
                $path = $request->file('avatar')->store('avatars', 'public');
                $photoUrl = '/storage/' . $path;
            } elseif ($request->hasFile('profile_photo')) {
                $path = $request->file('profile_photo')->store('avatars', 'public');
                $photoUrl = '/storage/' . $path;
            } else {
                $avatarStr = $request->input('avatar') ?: $request->input('profile_photo');
                if ($avatarStr && strpos($avatarStr, 'data:image') === 0) {
                    $imageParts = explode(';base64,', $avatarStr);
                    if (count($imageParts) === 2) {
                        $imageType = explode('/', $imageParts[0])[1] ?? 'png';
                        if (str_contains($imageType, ';')) $imageType = explode(';', $imageType)[0];
                        $imageDecoded = base64_decode($imageParts[1]);
                        $fileName = 'avatars/' . uniqid() . '.' . $imageType;
                        \Illuminate\Support\Facades\Storage::disk('public')->put($fileName, $imageDecoded);
                        $photoUrl = '/storage/' . $fileName;
                    }
                } elseif ($avatarStr && (str_starts_with($avatarStr, 'http') || str_starts_with($avatarStr, '/'))) {
                    $photoUrl = $avatarStr;
                }
            }

            $updateData = [];
            if ($request->filled('designation')) $updateData['designation'] = $request->designation;
            if ($request->filled('company')) $updateData['company'] = $request->company;
            if ($request->filled('specialization')) $updateData['specialization'] = $request->specialization;
            if ($request->filled('hourly_rate')) $updateData['hourly_rate'] = (float)$request->hourly_rate;
            if ($photoUrl) $updateData['profile_photo'] = $photoUrl;

            if (!empty($updateData)) {
                $profile->update($updateData);
            }
            \Illuminate\Support\Facades\Cache::flush();
        }

        return response()->json([
            'message' => 'User updated successfully',
            'user' => $user->load('roles')
        ]);
    }

    public function destroy(string $id)
    {
        $user = User::withTrashed()->find($id);
        if ($user) {
            $profile = \App\Models\ExpertProfile::where('user_id', $user->id)->first();
            if ($profile) {
                \App\Models\ExpertAvailability::where('expert_profile_id', $profile->id)->delete();
                \App\Models\ExpertCourseAssignment::where('expert_profile_id', $profile->id)->delete();
                \App\Models\MentorSession::where('expert_id', $profile->id)->orWhere('expert_profile_id', $profile->id)->delete();
                \App\Models\MentorBooking::where('expert_id', $profile->id)->delete();
                $profile->delete();
            }
            if (method_exists($user, 'roles')) {
                $user->roles()->detach();
            }
            $user->forceDelete();
            \Illuminate\Support\Facades\Cache::flush();
        }

        return response()->json(['message' => 'User deleted successfully']);
    }

    public function verifyProfile(Request $request, string $id)
    {
        $user = User::findOrFail($id);
        
        $role = $user->roles()->first()?->name;
        
        $profile = match($role) {
            'expert' => $user->expertProfile()->first(),
            'company' => $user->companyProfile()->first(),
            'college' => $user->collegeProfile()->first(),
            default => null
        };

        if (!$profile) {
            return response()->json(['message' => 'This user role does not require profile verification.'], 400);
        }

        $profile->update(['is_verified' => true]);

        // Send profile approved notification
        $user->notify(new PlatformNotification(
            "Profile Approved! 🎉",
            "Congratulations! Your profile as a " . ucfirst($role) . " has been successfully verified.",
            'profile_approved',
            ['role' => $role]
        ));

        return response()->json(['message' => "{$role} profile verified successfully."]);
    }

    public function export(Request $request)
    {
        $query = User::with('roles')
            ->when($request->role, function ($query, $role) {
                $query->role($role);
            })
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            });

        $users = $query->get();

        $headers = ['ID', 'Name', 'Email', 'Role', 'Status', 'Join Date'];
        
        $callback = function() use ($users, $headers) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $headers);
            
            foreach ($users as $user) {
                $roleName = $user->roles->first() ? $user->roles->first()->name : 'No Role';
                fputcsv($file, [
                    $user->id,
                    $user->first_name . ' ' . $user->last_name,
                    $user->email,
                    ucfirst($roleName),
                    $user->status,
                    $user->created_at?->format('Y-m-d H:i:s'),
                ]);
            }
            fclose($file);
        };

        return response()->streamDownload($callback, 'users_export_' . now()->format('Y-m-d_H-i-s') . '.csv', [
            'Content-Type' => 'text/csv',
            'Cache-Control' => 'no-cache, must-revalidate',
        ]);
    }
}

