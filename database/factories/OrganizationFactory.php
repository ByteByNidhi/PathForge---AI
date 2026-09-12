<?php

namespace Database\Factories;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Organization>
 */
class OrganizationFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(6)),
            'email' => fake()->unique()->companyEmail(),
            'phone' => fake()->optional()->numerify('+91 ##########'),
            'website' => 'https://example.com',
            'description' => fake()->sentence(),
            'logo_url' => null,
            'status' => Organization::STATUS_ACTIVE,
        ];
    }
}
