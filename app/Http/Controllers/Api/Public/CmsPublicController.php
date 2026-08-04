<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CmsCompany;
use App\Models\CmsPlacementPartner;
use App\Models\CmsCollege;
use App\Models\CmsPortfolio;

class CmsPublicController extends Controller
{
    public function getCompanies()
    {
        return response()->json(
            CmsCompany::with('industry')
                ->whereIn('status', ['published', 'Published', 'PUBLISHED', 'active', 'Active', 'ACTIVE'])
                ->orderBy('display_order')
                ->get()
        );
    }

    public function getPlacementPartners()
    {
        return response()->json(
            CmsPlacementPartner::with('industry')
                ->whereIn('status', ['published', 'Published', 'PUBLISHED', 'active', 'Active', 'ACTIVE'])
                ->orderBy('display_order')
                ->get()
                ->map(function ($partner) {
                    $partner->logo_url = $partner->logo_url ? asset('storage/' . $partner->logo_url) : null;
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
        );
    }

    public function getPortfolios()
    {
        return response()->json(
            CmsPortfolio::whereIn('status', ['published', 'Published', 'PUBLISHED', 'active', 'Active', 'ACTIVE'])
                ->orderBy('display_order')
                ->get()
        );
    }
}
