<?php

namespace Database\Factories;

use App\Models\Community;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Community>
 */
class CommunityFactory extends Factory
{
    protected $model = Community::class;

    public function definition(): array
    {
        $industries = [
            'Agriculture', 'AI & Machine Learning', 'Automation',
            'Construction', 'CRM', 'Developers',
            'ERP', 'Fleet Management', 'Healthcare',
            'Logistics', 'Manufacturing', 'Mining',
            'Startups', 'SaaS',
        ];

        $name = fake()->unique()->words(fake()->numberBetween(2, 3), true);
        $name = ucwords($name);

        return [
            'created_by' => User::factory(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(100, 999),
            'description' => fake()->paragraph(2),
            'industry' => fake()->randomElement($industries),
            'visibility' => fake()->randomElement(['public', 'public', 'public', 'private']),
            'members_count' => 0,
        ];
    }
}
