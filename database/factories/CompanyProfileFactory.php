<?php

namespace Database\Factories;

use App\Models\CompanyProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CompanyProfileFactory extends Factory
{
    protected $model = CompanyProfile::class;

    public function definition(): array
    {
        $industries = ['Information Technology', 'FinTech', 'Healthcare Tech', 'E-Commerce', 'EdTech'];
        $sizes = ['1-10', '11-50', '51-200', '201-500', '501-1000', '1000+'];

        return [
            'user_id' => User::factory(),
            'company_name' => fake()->company(),
            'industry' => fake()->randomElement($industries),
            'company_size' => fake()->randomElement($sizes),
            'website' => 'https://www.' . fake()->domainName(),
            'logo' => 'https://ui-avatars.com/api/?name=' . urlencode(fake()->company()) . '&background=random&color=fff',
        ];
    }
}
