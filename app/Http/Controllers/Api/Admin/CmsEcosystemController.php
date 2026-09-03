<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CmsCompany;
use App\Models\CmsPlacementPartner;
use App\Models\CmsCollege;
use App\Models\CmsPortfolio;
use App\Models\CmsIndustry;
use Illuminate\Support\Str;

class CmsEcosystemController extends Controller
{
    // === COMPANIES ===
    public function getCompanies()
    {
        $companies = CmsCompany::with('industry')
            ->orderBy('display_order')
            ->get()
            ->map(function ($company) {
                if ($company->logo_url && !str_starts_with($company->logo_url, 'http') && !str_starts_with($company->logo_url, '/')) {
                    $company->logo_url = '/' . $company->logo_url;
                }
                if ($company->logo_url && !str_starts_with($company->logo_url, 'http')) {
                    $frontendPath = base_path('../Frontend_BB_fixed_v4/public' . urldecode($company->logo_url));
                    if (!file_exists($frontendPath)) {
                        $company->logo_url = 'https://ui-avatars.com/api/?name=' . urlencode($company->name) . '&background=1B2A6B&color=fff&bold=true&format=svg';
                    }
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
            'industry_id' => 'nullable|exists:cms_industries,id',
            'industry' => 'nullable|string',
            'website_url' => 'nullable|string',
            'status' => 'nullable|in:published,draft,archived',
            'is_featured' => 'boolean'
        ]);

        if (empty($validated['industry_id']) && !empty($request->input('industry'))) {
            $ind = CmsIndustry::firstOrCreate(
                ['name' => trim($request->input('industry'))],
                ['slug' => Str::slug($request->input('industry'))]
            );
            $validated['industry_id'] = $ind->id;
        }
        unset($validated['industry']);

        if (!empty($validated['logo_url']) && !str_starts_with($validated['logo_url'], 'http') && !str_starts_with($validated['logo_url'], '/')) {
            $validated['logo_url'] = '/' . $validated['logo_url'];
        }

        $validated['slug'] = Str::slug($validated['name']) . '-' . time();
        $company = CmsCompany::create($validated);
        return response()->json($company->load('industry'), 201);
    }

    public function updateCompany(Request $request, $id)
    {
        $company = CmsCompany::findOrFail($id);
        $validated = $request->validate([
            'name' => 'sometimes|string',
            'logo_url' => 'nullable|string',
            'industry_id' => 'nullable|exists:cms_industries,id',
            'industry' => 'nullable|string',
            'website_url' => 'nullable|string',
            'status' => 'nullable|in:published,draft,archived',
            'is_featured' => 'boolean'
        ]);

        if (empty($validated['industry_id']) && !empty($request->input('industry'))) {
            $ind = CmsIndustry::firstOrCreate(
                ['name' => trim($request->input('industry'))],
                ['slug' => Str::slug($request->input('industry'))]
            );
            $validated['industry_id'] = $ind->id;
        }
        unset($validated['industry']);

        if (!empty($validated['logo_url']) && !str_starts_with($validated['logo_url'], 'http') && !str_starts_with($validated['logo_url'], '/')) {
            $validated['logo_url'] = '/' . $validated['logo_url'];
        }

        if (isset($validated['name']) && $validated['name'] !== $company->name) {
            $validated['slug'] = Str::slug($validated['name']) . '-' . time();
        }

        $company->update($validated);
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
        return response()->json(CmsPlacementPartner::with('industry')->orderBy('display_order')->get());
    }

    public function storePlacementPartner(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'logo_url' => 'nullable|string',
            'industry_id' => 'nullable|exists:cms_industries,id',
            'is_featured' => 'boolean'
        ]);

        $validated['slug'] = Str::slug($validated['name']) . '-' . time();
        $partner = CmsPlacementPartner::create($validated);
        return response()->json($partner, 201);
    }

    public function updatePlacementPartner(Request $request, $id)
    {
        $partner = CmsPlacementPartner::findOrFail($id);
        $validated = $request->validate([
            'name' => 'sometimes|string',
            'logo_url' => 'nullable|string',
            'industry_id' => 'nullable|exists:cms_industries,id',
            'is_featured' => 'boolean'
        ]);

        if (isset($validated['name']) && $validated['name'] !== $partner->name) {
            $validated['slug'] = Str::slug($validated['name']) . '-' . time();
        }

        $partner->update($validated);
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
        return response()->json(CmsCollege::orderBy('display_order')->get());
    }

    public function storeCollege(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'location' => 'nullable|string',
            'logo_url' => 'nullable|string',
            'banner_image' => 'nullable|string',
            'is_featured' => 'boolean',
            'status' => 'nullable|in:published,draft,archived',
            'short_description' => 'nullable|string',
            'full_description' => 'nullable|string',
            'website_url' => 'nullable|string',
            'is_ugc_approved' => 'boolean',
            'naac_grade' => 'nullable|string',
            'nirf_ranking' => 'nullable|string',
            'is_wes_approved' => 'boolean',
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
            'is_featured' => 'boolean',
            'status' => 'nullable|in:published,draft,archived',
            'short_description' => 'nullable|string',
            'full_description' => 'nullable|string',
            'website_url' => 'nullable|string',
            'is_ugc_approved' => 'boolean',
            'naac_grade' => 'nullable|string',
            'nirf_ranking' => 'nullable|string',
            'is_wes_approved' => 'boolean',
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
        return response()->json(CmsPortfolio::orderBy('display_order')->get());
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
            'tags' => 'nullable|string', // Comma separated from frontend
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
        
        // Tags can come as an array from frontend during edit
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
            $validated['tags'] = []; // default to empty if not provided
        }

        $portfolio->update($validated);
        return response()->json($portfolio);
    }

    public function deletePortfolio($id)
    {
        CmsPortfolio::findOrFail($id)->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
