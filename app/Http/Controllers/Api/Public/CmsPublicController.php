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
                ->where('status', 'published')
                ->orderBy('display_order')
                ->get()
        );
    }

    public function getPlacementPartners()
    {
        return response()->json(
            CmsPlacementPartner::with('industry')
                ->where('status', 'published')
                ->orderBy('display_order')
                ->get()
        );
    }

    public function getColleges()
    {
        return response()->json(
            CmsCollege::where('status', 'published')
                ->orderBy('display_order')
                ->get()
        );
    }

    public function getPortfolios()
    {
        return response()->json(
            CmsPortfolio::where('status', 'published')
                ->orderBy('display_order')
                ->get()
        );
    }
}
