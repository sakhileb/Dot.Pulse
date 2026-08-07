<?php

namespace Database\Factories;

use App\Models\Community;
use App\Models\PulsePost;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PulsePost>
 */
class PulsePostFactory extends Factory
{
    protected $model = PulsePost::class;

    public function definition(): array
    {
        $type = fake()->randomElement(PulsePost::$types);

        $titles = [
            'discussion' => ['What do you think about...', 'Let\'s talk about', 'Your experience with'],
            'question' => ['How do I...', 'What\'s the best way to...', 'Anyone else having issues with'],
            'idea' => ['Idea: What if we could...', 'Feature request:', 'Proposal:'],
            'bug_report' => ['Bug found in', 'Issue with', 'Problem: '],
            'announcement' => ['Exciting news:', 'Announcing', 'We\'re launching'],
            'release' => ['v2.0 is here', 'New release:', 'What\'s new in'],
            'success_story' => ['How we saved', 'Case study:', 'Our journey with'],
            'showcase' => ['Check out what we built', 'Showcasing our', 'Built this with'],
            'tutorial' => ['How to setup', 'Step-by-step guide:', 'Getting started with'],
            'article' => ['Deep dive:', 'The complete guide to', 'Understanding'],
        ];

        $titlePrefix = isset($titles[$type])
            ? fake()->randomElement($titles[$type]).' '.fake()->words(3, true)
            : fake()->sentence(5);

        return [
            'user_id' => User::factory(),
            'community_id' => null,
            'type' => $type,
            'title' => $titlePrefix,
            'body' => fake()->paragraphs(fake()->numberBetween(1, 4), true),
            'status' => 'published',
            'is_pinned' => false,
            'views_count' => fake()->numberBetween(0, 500),
            'comments_count' => 0,
            'reactions_count' => fake()->numberBetween(0, 50),
            'ai_relevance_score' => fake()->randomFloat(2, 0.3, 1.0),
        ];
    }

    public function forCommunity(Community $community): static
    {
        return $this->state(['community_id' => $community->id]);
    }
}
