<?php

namespace App\Http\Controllers\Api\College;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CollegeCompanyController extends Controller
{
    public function index(Request $request)
    {
        $companies = $request->user()->partnerCompanies()->get();
        return response()->json(['success' => true, 'data' => $companies]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:users,id'
        ]);

        $request->user()->partnerCompanies()->syncWithoutDetaching([
            $validated['company_id'] => ['status' => 'active']
        ]);

        return response()->json(['success' => true, 'message' => 'Company partnered successfully']);
    }

    public function destroy(Request $request, $id)
    {
        $request->user()->partnerCompanies()->detach($id);
        return response()->json(['success' => true, 'message' => 'Partnership removed successfully']);
    }

    public function search(Request $request)
    {
        $query = $request->query('q');
        
        if (empty($query) || strlen($query) < 2) {
            return response()->json(['success' => true, 'data' => []]);
        }

        $collegeId = $request->user()->id;

        // Get companies matching query that are not already partners
        $companies = \App\Models\User::role('company')
            ->where(function($q) use ($query) {
                $q->where('first_name', 'like', "%{$query}%")
                  ->orWhere('last_name', 'like', "%{$query}%")
                  ->orWhere('email', 'like', "%{$query}%")
                  ->orWhereHas('companyProfile', function($profileQuery) use ($query) {
                      $profileQuery->where('company_name', 'like', "%{$query}%");
                  });
            })
            ->whereNotIn('id', function($q) use ($collegeId) {
                $q->select('company_id')
                  ->from('college_company_partnerships')
                  ->where('college_id', $collegeId);
            })
            ->with('companyProfile')
            ->take(10)
            ->get()
            ->map(function ($company) {
                return [
                    'id' => $company->id,
                    'name' => $company->companyProfile->company_name ?? $company->name,
                    'email' => $company->email,
                ];
            });

        return response()->json(['success' => true, 'data' => $companies]);
    }
}
