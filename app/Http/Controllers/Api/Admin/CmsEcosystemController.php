<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CmsCompany;
use App\Models\CmsPlacementPartner;
use App\Models\CmsCollege;
use App\Models\CmsPortfolio;
use App\Models\CmsIndustry;
use App\Models\StudentJobOffer;
use App\Support\StorageHelper;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class CmsEcosystemController extends Controller
{
    // === COMPANIES ===
    public function getCompanies()
    {
        $companies = CmsCompany::with('industry')
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

    public function storeCompany(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'logo_url' => 'nullable|string',
            'logo' => 'nullable|file|max:10240',
            'industry_id' => 'nullable|exists:cms_industries,id',
            'industry' => 'nullable|string',
            'website_url' => 'nullable|string',
            'location' => 'nullable|string',
            'status' => 'nullable|in:published,draft,archived',
            'is_featured' => 'nullable|boolean'
        ]);

        if ($request->hasFile('logo') || $request->hasFile('file') || $request->hasFile('image')) {
            $file = $request->file('logo') ?? $request->file('file') ?? $request->file('image');
            $path = $file->store('companies/logos', 'public');
            $validated['logo_url'] = '/storage/' . $path;
        }

        if (empty($validated['industry_id']) && !empty($request->input('industry'))) {
            $ind = CmsIndustry::firstOrCreate(
                ['name' => trim($request->input('industry'))],
                ['slug' => Str::slug($request->input('industry'))]
            );
            $validated['industry_id'] = $ind->id;
        }
        unset($validated['industry'], $validated['logo']);

        $validated['slug'] = Str::slug($validated['name']) . '-' . time();
        $company = CmsCompany::create($validated);
        
        $company->logo_url = StorageHelper::url($company->logo_url);
        return response()->json($company->load('industry'), 201);
    }

    public function updateCompany(Request $request, $id)
    {
        $company = CmsCompany::findOrFail($id);
        $validated = $request->validate([
            'name' => 'sometimes|string',
            'logo_url' => 'nullable|string',
            'logo' => 'nullable|file|max:10240',
            'industry_id' => 'nullable|exists:cms_industries,id',
            'industry' => 'nullable|string',
            'website_url' => 'nullable|string',
            'location' => 'nullable|string',
            'status' => 'nullable|in:published,draft,archived',
            'is_featured' => 'nullable|boolean'
        ]);

        if ($request->hasFile('logo') || $request->hasFile('file') || $request->hasFile('image')) {
            $file = $request->file('logo') ?? $request->file('file') ?? $request->file('image');
            $path = $file->store('companies/logos', 'public');
            $validated['logo_url'] = '/storage/' . $path;
        }

        if (empty($validated['industry_id']) && !empty($request->input('industry'))) {
            $ind = CmsIndustry::firstOrCreate(
                ['name' => trim($request->input('industry'))],
                ['slug' => Str::slug($request->input('industry'))]
            );
            $validated['industry_id'] = $ind->id;
        }
        unset($validated['industry'], $validated['logo']);

        if (isset($validated['name']) && $validated['name'] !== $company->name) {
            $validated['slug'] = Str::slug($validated['name']) . '-' . time();
        }

        $company->update($validated);
        $company->logo_url = StorageHelper::url($company->logo_url);
        return response()->json($company->load('industry'));
    }

    public function deleteCompany($id)
    {
        CmsCompany::findOrFail($id)->delete();
        return response()->json(['message' => 'Deleted']);
    }

    // === PLACEMENT PARTNERS ===
    public function getPlacementPartners()
    {
        $partners = CmsPlacementPartner::with('industry')
            ->orderBy('display_order')
            ->get()
            ->map(function ($partner) {
                if ($partner->logo_url) {
                    $partner->logo_url = StorageHelper::url($partner->logo_url);
                }
                return $partner;
            });

        return response()->json($partners);
    }

    public function storePlacementPartner(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'logo_url' => 'nullable|string',
            'logo' => 'nullable|file|max:10240',
            'industry_id' => 'nullable|exists:cms_industries,id',
            'is_featured' => 'nullable|boolean'
        ]);

        if ($request->hasFile('logo') || $request->hasFile('file') || $request->hasFile('image')) {
            $file = $request->file('logo') ?? $request->file('file') ?? $request->file('image');
            $path = $file->store('partners/logos', 'public');
            $validated['logo_url'] = '/storage/' . $path;
        }
        unset($validated['logo']);

        $validated['slug'] = Str::slug($validated['name']) . '-' . time();
        $partner = CmsPlacementPartner::create($validated);
        $partner->logo_url = StorageHelper::url($partner->logo_url);
        return response()->json($partner, 201);
    }

    public function updatePlacementPartner(Request $request, $id)
    {
        $partner = CmsPlacementPartner::findOrFail($id);
        $validated = $request->validate([
            'name' => 'sometimes|string',
            'logo_url' => 'nullable|string',
            'logo' => 'nullable|file|max:10240',
            'industry_id' => 'nullable|exists:cms_industries,id',
            'is_featured' => 'nullable|boolean'
        ]);

        if ($request->hasFile('logo') || $request->hasFile('file') || $request->hasFile('image')) {
            $file = $request->file('logo') ?? $request->file('file') ?? $request->file('image');
            $path = $file->store('partners/logos', 'public');
            $validated['logo_url'] = '/storage/' . $path;
        }
        unset($validated['logo']);

        if (isset($validated['name']) && $validated['name'] !== $partner->name) {
            $validated['slug'] = Str::slug($validated['name']) . '-' . time();
        }

        $partner->update($validated);
        $partner->logo_url = StorageHelper::url($partner->logo_url);
        return response()->json($partner);
    }

    public function deletePlacementPartner($id)
    {
        CmsPlacementPartner::findOrFail($id)->delete();
        return response()->json(['message' => 'Deleted']);
    }

    // === COLLEGES ===
    public function getColleges()
    {
        $colleges = CmsCollege::orderBy('display_order')->get()->map(function ($college) {
            if ($college->logo_url) {
                $college->logo_url = StorageHelper::url($college->logo_url);
            }
            if ($college->banner_image) {
                $college->banner_image = StorageHelper::url($college->banner_image);
            }
            return $college;
        });

        return response()->json($colleges);
    }

    public function storeCollege(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'location' => 'nullable|string',
            'logo_url' => 'nullable|string',
            'banner_image' => 'nullable|string',
            'is_featured' => 'nullable|boolean',
            'status' => 'nullable|in:published,draft,archived',
            'short_description' => 'nullable|string',
            'full_description' => 'nullable|string',
            'website_url' => 'nullable|string',
            'is_ugc_approved' => 'nullable|boolean',
            'naac_grade' => 'nullable|string',
            'nirf_ranking' => 'nullable|string',
            'is_wes_approved' => 'nullable|boolean',
            'degree_types' => 'nullable|array',
            'popular_courses' => 'nullable|array',
            'duration' => 'nullable|string',
            'eligibility' => 'nullable|string',
            'admission_process' => 'nullable|string',
            'placement_support' => 'nullable|string',
            'career_services' => 'nullable|string',
            'accreditation' => 'nullable|string',
            'seo_title' => 'nullable|string',
            'seo_description' => 'nullable|string',
            'meta_keywords' => 'nullable|string',
        ]);

        $validated['slug'] = Str::slug($validated['name']) . '-' . time();
        $college = CmsCollege::create($validated);
        return response()->json($college, 201);
    }

    public function updateCollege(Request $request, $id)
    {
        $college = CmsCollege::findOrFail($id);
        $validated = $request->validate([
            'name' => 'sometimes|string',
            'location' => 'nullable|string',
            'logo_url' => 'nullable|string',
            'banner_image' => 'nullable|string',
            'is_featured' => 'nullable|boolean',
            'status' => 'nullable|in:published,draft,archived',
            'short_description' => 'nullable|string',
            'full_description' => 'nullable|string',
            'website_url' => 'nullable|string',
            'is_ugc_approved' => 'nullable|boolean',
            'naac_grade' => 'nullable|string',
            'nirf_ranking' => 'nullable|string',
            'is_wes_approved' => 'nullable|boolean',
            'degree_types' => 'nullable|array',
            'popular_courses' => 'nullable|array',
            'duration' => 'nullable|string',
            'eligibility' => 'nullable|string',
            'admission_process' => 'nullable|string',
            'placement_support' => 'nullable|string',
            'career_services' => 'nullable|string',
            'accreditation' => 'nullable|string',
            'seo_title' => 'nullable|string',
            'seo_description' => 'nullable|string',
            'meta_keywords' => 'nullable|string',
        ]);

        if (isset($validated['name']) && $validated['name'] !== $college->name) {
            $validated['slug'] = Str::slug($validated['name']) . '-' . time();
        }

        $college->update($validated);
        return response()->json($college);
    }

    public function deleteCollege($id)
    {
        CmsCollege::findOrFail($id)->delete();
        return response()->json(['message' => 'Deleted']);
    }

    // === PORTFOLIOS ===
    public function getPortfolios()
    {
        $portfolios = CmsPortfolio::orderBy('display_order')->get()->map(function ($p) {
            if ($p->image_url) {
                $p->image_url = StorageHelper::url($p->image_url);
            }
            return $p;
        });

        return response()->json($portfolios);
    }

    public function storePortfolio(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string',
            'studio' => 'required|string',
            'category' => 'required|string',
            'description' => 'nullable|string',
            'duration' => 'nullable|string',
            'deliverables' => 'nullable|string',
            'image_url' => 'nullable|string',
            'tags' => 'nullable|string',
        ]);

        $validated['slug'] = Str::slug($validated['title']) . '-' . time();
        if (isset($validated['tags'])) {
            $validated['tags'] = array_map('trim', explode(',', $validated['tags']));
        }

        $portfolio = CmsPortfolio::create($validated);
        return response()->json($portfolio, 201);
    }

    public function updatePortfolio(Request $request, $id)
    {
        $portfolio = CmsPortfolio::findOrFail($id);
        
        $rules = [
            'title' => 'sometimes|string',
            'studio' => 'sometimes|string',
            'category' => 'sometimes|string',
            'description' => 'nullable|string',
            'duration' => 'nullable|string',
            'deliverables' => 'nullable|string',
            'image_url' => 'nullable|string',
            'tags' => 'nullable', 
        ];

        $validated = $request->validate($rules);

        if (isset($validated['title']) && $validated['title'] !== $portfolio->title) {
            $validated['slug'] = Str::slug($validated['title']) . '-' . time();
        }
        
        if (isset($validated['tags']) && is_string($validated['tags'])) {
            $validated['tags'] = array_map('trim', explode(',', $validated['tags']));
        } elseif (isset($validated['tags']) && is_array($validated['tags'])) {
            // keep as is
        } else {
            $validated['tags'] = [];
        }

        $portfolio->update($validated);
        return response()->json($portfolio);
    }

    public function deletePortfolio($id)
    {
        CmsPortfolio::findOrFail($id)->delete();
        return response()->json(['message' => 'Deleted']);
    }

    // === JOB OFFERS / STUDENT SHOWCASE PERSISTENCE ===
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
            // Clear existing records and replace with updated showcase list
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
}
