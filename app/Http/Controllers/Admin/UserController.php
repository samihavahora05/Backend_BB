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
        // Admin creating a user via the user manager panel
        $request->validate([
            'name'  => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'role'  => 'required|string|exists:roles,name',
            'password' => 'required|min:6',
        ]);

        $user = User::create([
            'first_name' => explode(' ', $request->name)[0],
            'last_name'  => implode(' ', array_slice(explode(' ', $request->name), 1)) ?: '',
            'email'      => $request->email,
            'password'   => \Illuminate\Support\Facades\Hash::make($request->password),
            'status'     => 'active',
        ]);

        $user->assignRole($request->role);

        return response()->json(['success' => true, 'data' => $user->load('roles')], 201);
    }


    public function show(string $id)
    {
        $user = User::with('roles')->findOrFail($id);
        
        // Load the specific profile based on the role
        $role = $user->roles()->first()?->name;
        $profileRelation = match($role) {
            'student' => 'studentProfile',
            'expert' => 'expertProfile',
            'company' => 'companyProfile',
            'college' => 'collegeProfile',
            'intern' => 'internProfile',
            'job-seeker' => 'jobSeekerProfile',
            default => null
        };

        if ($profileRelation) {
            $user->load($profileRelation);
        }

        return response()->json($user);
    }

    public function update(Request $request, string $id)
    {
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'role' => 'sometimes|string|exists:roles,name'
        ]);

        $user = User::findOrFail($id);
        
        if ($request->has('name')) {
            $user->name = $request->name;
            $user->save();
        }

        if ($request->has('role')) {
            $user->syncRoles([$request->role]);
        }

        return response()->json([
            'message' => 'User updated successfully',
            'user' => $user->load('roles')
        ]);
    }

    public function destroy(string $id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return response()->json(['message' => 'User soft deleted successfully']);
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

