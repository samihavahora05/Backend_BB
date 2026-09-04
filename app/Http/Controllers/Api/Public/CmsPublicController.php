<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CmsCompany;
use App\Models\CmsPlacementPartner;
use App\Models\CmsCollege;
use App\Models\CmsPortfolio;
use App\Models\StudentJobOffer;
use App\Support\StorageHelper;
use Illuminate\Support\Facades\DB;

class CmsPublicController extends Controller
{
    public function getCompanies()
    {
        $companies = CmsCompany::with('industry')
            ->where(function ($q) {
                $q->whereNull('status')
                  ->orWhereIn('status', ['published', 'Published', 'PUBLISHED', 'active', 'Active', 'ACTIVE']);
            })
            ->orderBy('display_order')
            ->get()
            ->map(function ($company) {
                if ($company->logo_url) {
                    $company->logo_url = StorageHelper::url($company->logo_url);
                }
                return $company;
            });

        return response()->json($companies);
    }

    public function getPlacementPartners()
    {
        return response()->json(
            CmsPlacementPartner::with('industry')
                ->whereIn('status', ['published', 'Published', 'PUBLISHED', 'active', 'Active', 'ACTIVE'])
                ->orderBy('display_order')
                ->get()
                ->map(function ($partner) {
                    $partner->logo_url = $partner->logo_url ? StorageHelper::url($partner->logo_url) : null;
                    return $partner;
                })
        );
    }

    public function getColleges()
    {
        return response()->json(
            CmsCollege::whereIn('status', ['published', 'Published', 'PUBLISHED', 'active', 'Active', 'ACTIVE'])
                ->orderBy('display_order')
                ->get()
                ->map(function ($college) {
                    if ($college->logo_url) {
                        $college->logo_url = StorageHelper::url($college->logo_url);
                    }
                    if ($college->banner_image) {
                        $college->banner_image = StorageHelper::url($college->banner_image);
                    }
                    return $college;
                })
        );
    }

    public function getCollegeBySlug($slug)
    {
        $college = CmsCollege::where('slug', $slug)
            ->whereIn('status', ['published', 'Published', 'PUBLISHED', 'active', 'Active', 'ACTIVE'])
            ->firstOrFail();

        if ($college->logo_url) {
            $college->logo_url = StorageHelper::url($college->logo_url);
        }
        if ($college->banner_image) {
            $college->banner_image = StorageHelper::url($college->banner_image);
        }
            
        return response()->json($college);
    }

    public function getPortfolios()
    {
        return response()->json(
            CmsPortfolio::whereIn('status', ['published', 'Published', 'PUBLISHED', 'active', 'Active', 'ACTIVE'])
                ->orderBy('display_order')
                ->get()
                ->map(function ($p) {
                    if ($p->image_url) {
                        $p->image_url = StorageHelper::url($p->image_url);
                    }
                    return $p;
                })
        );
    }

    public function getJobOffers()
    {
        $offers = StudentJobOffer::where('is_active', true)
            ->orderBy('id', 'asc')
            ->get()
            ->map(function ($offer) {
                if ($offer->avatar_url) {
                    $offer->avatar_url = StorageHelper::url($offer->avatar_url);
                }
                return $offer;
            });

        return response()->json($offers);
    }

    public function saveJobOffers(Request $request)
    {
        $students = $request->input('students', []);
        if (!is_array($students) || empty($students)) {
            return response()->json(['message' => 'No student records provided.'], 400);
        }

        DB::beginTransaction();
        try {
            StudentJobOffer::truncate();

            foreach ($students as $index => $st) {
                $name = $st['student_name'] ?? $st['name'] ?? ('Student ' . ($index + 1));
                $img = $st['image_url'] ?? $st['avatar_url'] ?? $st['image'] ?? null;

                StudentJobOffer::create([
                    'student_name' => $name,
                    'degree'       => $st['degree'] ?? 'Alumni',
                    'company_name' => $st['company_name'] ?? $st['company'] ?? 'Partner Enterprise',
                    'role'         => $st['role'] ?? $st['designation'] ?? 'Graphic Design',
                    'offered_on'   => $st['offered_on'] ?? now()->format('d M Y'),
                    'package'      => $st['package'] ?? 'Best in Industry',
                    'avatar_url'   => $img,
                    'is_active'    => true,
                ]);
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Student showcase saved successfully.']);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Failed to save student showcase: ' . $e->getMessage()], 500);
        }
    }

    public function getTestimonials()
    {
        try {
            $testimonials = DB::table('testimonials')
                ->whereIn('status', ['active', 'Active', 'published', 'Published', 'PUBLISHED'])
                ->orderBy('display_order', 'asc')
                ->get()
                ->map(function ($t) {
                    $rawImg = $t->image_url ?? $t->photo_url ?? null;
                    return [
                        'id'          => $t->id,
                        'name'        => $t->name,
                        'designation' => $t->designation ?? $t->role ?? 'Alumni',
                        'company'     => $t->company ?? 'Partner Company',
                        'review'      => $t->review ?? $t->content ?? '',
                        'content'     => $t->review ?? $t->content ?? '',
                        'rating'      => (int)($t->rating ?? 5),
                        'image_url'   => $rawImg ? StorageHelper::url($rawImg) : null,
                        'type'        => $t->type ?? 'job'
                    ];
                });

            return response()->json($testimonials);
        } catch (\Throwable $e) {
            return response()->json([]);
        }
    }
}
