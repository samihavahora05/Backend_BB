<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use App\Models\PlacementPartner;
use App\Models\Testimonial;
use App\Models\User;
use App\Models\Internship;
use App\Models\ExpertProfile;
use Illuminate\Support\Facades\Cache;

class PublicCmsController extends Controller
{
    public function stats()
    {
        return Cache::remember('public.cms.stats.v3', now()->addHours(1), function () {
            $studentsCount = 5000;
            $placementsCount = 4000;
            $projectsCount = 3000; 
            $partnersCount = 250;
            $clientsCount = 1500;
            $industriesCount = 15;
            $experienceYears = 12;
            $commitmentRate = 100;

            return response()->json([
                'success' => true,
                'data' => [
                    'students' => $studentsCount,
                    'placed' => $placementsCount,
                    'projects' => $projectsCount,
                    'partners' => $partnersCount,
                    'clients' => $clientsCount,
                    'industries' => $industriesCount,
                    'experience_years' => $experienceYears,
                    'commitment_rate' => $commitmentRate,
                ]
            ]);
        });
    }

    public function settings()
    {
        return Cache::remember('public.cms.settings.v2', now()->addHours(6), function () {
            $settings = \App\Models\GlobalSetting::where('group', 'general')
                ->pluck('value', 'key')->toArray();
                
            $preloaderSettings = \App\Models\SystemSetting::where('group', 'preloader')
                ->pluck('value', 'key')->toArray();
                
            if (!empty($preloaderSettings)) {
                $settings['preloader'] = $preloaderSettings;
            }
                
            try {
                $credentials = \App\Models\SystemApiCredential::where('status', true)->get();
                foreach ($credentials as $cred) {
                    if ($cred->provider === 'razorpay') $settings['razorpay_key'] = $cred->api_key;
                    if ($cred->provider === 'stripe') $settings['stripe_key'] = $cred->api_key;
                    if ($cred->provider === 'google_maps') $settings['google_maps_key'] = $cred->api_key;
                    if ($cred->provider === 'google_oauth') $settings['google_oauth_client_id'] = $cred->api_key;
                }
            } catch (\Exception $e) {}
                
            return response()->json([
                'success' => true,
                'data' => $settings
            ]);
        });
    }

    public function faqs()
    {
        return Cache::remember('public.cms.faqs', now()->addHours(6), function () {
            return response()->json([
                'success' => true,
                'data' => Faq::where('is_active', true)->orderBy('order')->get()
            ]);
        });
    }

    public function partners()
    {
        return Cache::remember('public.cms.partners', now()->addHours(6), function () {
            return response()->json([
                'success' => true,
                'data' => PlacementPartner::where('is_active', true)->orderBy('order')->get()
            ]);
        });
    }

    public function testimonials()
    {
        return Cache::remember('public.cms.testimonials', now()->addHours(6), function () {
            return response()->json([
                'success' => true,
                'data' => Testimonial::where('is_active', true)->orderBy('order')->get()
            ]);
        });
    }
}
