<?php

namespace Database\Factories;

use App\Models\PulseProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PulseProfile>
 */
class PulseProfileFactory extends Factory
{
    protected $model = PulseProfile::class;

    public function definition(): array
    {
        $roles = ['customer', 'customer', 'customer', 'business', 'developer', 'partner'];

        $skillSets = [
            ['Laravel', 'PHP', 'Livewire'],
            ['Fleet Management', 'Logistics', 'GPS Tracking'],
            ['AI Automation', 'OpenAI', 'Prompt Engineering'],
            ['ERP', 'CRM', 'Business Intelligence'],
            ['Agriculture', 'Precision Farming', 'IoT'],
            ['Mining', 'Safety Systems', 'Operations'],
        ];

        return [
            'user_id' => User::factory(),
            'headline' => fake()->sentence(6),
            'bio' => fake()->paragraph(2),
            'location' => fake()->city().', '.fake()->countryCode(),
            'website' => fake()->boolean(40) ? fake()->url() : null,
            'skills' => fake()->randomElement($skillSets),
            'expertise_tags' => [],
            'role' => fake()->randomElement($roles),
            'community_points' => fake()->numberBetween(0, 2500),
            'solutions_accepted' => fake()->numberBetween(0, 30),
            'is_verified' => fake()->boolean(15),
        ];
    }
}
