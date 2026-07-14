<?php

namespace Database\Seeders;

use App\Models\Community;
use App\Models\CommunityMembership;
use App\Models\PulseBadge;
use App\Models\PulseComment;
use App\Models\PulseFollower;
use App\Models\PulseHashtag;
use App\Models\PulseMarketplaceItem;
use App\Models\PulsePost;
use App\Models\PulsePostEnrichment;
use App\Models\PulseProfile;
use App\Models\PulseReaction;
use App\Models\Team;
use App\Models\User;
use Database\Factories\CommunityFactory;
use Database\Factories\PulseCommentFactory;
use Database\Factories\PulsePostFactory;
use Database\Factories\PulseProfileFactory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PulseSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1. Admin user ──────────────────────────────────────────────
        $admin = User::firstOrCreate(
            ['email' => 'admin@pulse.test'],
            [
                'name'              => 'Pulse Admin',
                'password'          => Hash::make('password'),
                'email_verified_at' => now(),
            ],
        );
        $adminTeam = Team::firstOrCreate(
            ['user_id' => $admin->id, 'personal_team' => true],
            ['name' => "Pulse Admin's Team"],
        );
        $admin->update(['current_team_id' => $adminTeam->id]);

        PulseProfile::firstOrCreate(
            ['user_id' => $admin->id],
            [
                'headline'          => 'Platform Administrator',
                'bio'               => 'Keeping the community healthy and productive.',
                'role'              => 'admin',
                'community_points'  => 9999,
                'is_verified'       => true,
            ],
        );

        // ── 2. Regular users (15) ──────────────────────────────────────
        $users = collect();
        for ($i = 0; $i < 15; $i++) {
            $user = User::factory()->withPersonalTeam()->create();
            PulseProfile::factory()->create(['user_id' => $user->id]);
            $users->push($user);
        }
        $allUsers = $users->push($admin);

        // ── 3. Communities (8) ─────────────────────────────────────────
        $communityData = [
            ['name' => 'Fleet Automation Hub',    'industry' => 'Fleet Management'],
            ['name' => 'AI Builders',              'industry' => 'AI & Machine Learning'],
            ['name' => 'Laravel & Livewire',       'industry' => 'Developers'],
            ['name' => 'Agriculture Tech',         'industry' => 'Agriculture'],
            ['name' => 'Mining Operations',        'industry' => 'Mining'],
            ['name' => 'Startup Founders',         'industry' => 'Startups'],
            ['name' => 'ERP & CRM Pros',           'industry' => 'ERP'],
            ['name' => 'Healthcare Automation',    'industry' => 'Healthcare'],
        ];

        $communities = collect();
        foreach ($communityData as $data) {
            $creator = $allUsers->random();
            $community = Community::create([
                'created_by'    => $creator->id,
                'name'          => $data['name'],
                'slug'          => Str::slug($data['name']),
                'description'   => fake()->paragraph(2),
                'industry'      => $data['industry'],
                'visibility'    => 'public',
                'members_count' => 0,
            ]);

            // Creator is admin member
            CommunityMembership::create([
                'community_id' => $community->id,
                'user_id'      => $creator->id,
                'role'         => 'admin',
            ]);
            $community->increment('members_count');

            // Add 5-10 random members
            $members = $allUsers->where('id', '!=', $creator->id)->random(rand(5, 10));
            foreach ($members as $member) {
                CommunityMembership::firstOrCreate(
                    ['community_id' => $community->id, 'user_id' => $member->id],
                    ['role' => 'member'],
                );
                $community->increment('members_count');
            }

            $communities->push($community);
        }

        // ── 4. Hashtags ────────────────────────────────────────────────
        $hashtags = collect(['laravel', 'ai', 'automation', 'fleet', 'mining', 'agriculture', 'startup', 'erp', 'saas', 'dotpulse'])
            ->map(fn ($name) => PulseHashtag::firstOrCreate(['name' => $name], ['posts_count' => 0]));

        // ── 5. Posts (60) ──────────────────────────────────────────────
        $posts = collect();
        for ($i = 0; $i < 60; $i++) {
            $author    = $allUsers->random();
            $community = $communities->random();

            $post = PulsePost::factory()
                ->forCommunity($community)
                ->create(['user_id' => $author->id]);

            // Attach 1-3 hashtags
            $randomTags = $hashtags->random(rand(1, 3));
            $post->hashtags()->attach($randomTags->pluck('id'));
            foreach ($randomTags as $tag) {
                $tag->increment('posts_count');
            }

            // Auto-enrich with mock data
            PulsePostEnrichment::create([
                'pulse_post_id'       => $post->id,
                'summary'             => fake()->sentence(15),
                'tags'                => fake()->words(3),
                'sentiment'           => fake()->randomElement(['positive', 'positive', 'neutral', 'mixed']),
                'topics'              => fake()->words(2),
                'keywords'            => fake()->words(4),
                'language'            => 'en',
                'spam_score'          => fake()->randomFloat(2, 0, 0.1),
                'safety_score'        => fake()->randomFloat(2, 0.85, 1.0),
                'business_relevance'  => fake()->randomFloat(2, 0.5, 1.0),
                'community_score'     => fake()->randomFloat(2, 0.4, 0.9),
                'moderation_status'   => 'approved',
            ]);

            $posts->push($post);
        }

        // ── 6. Comments (120) ─────────────────────────────────────────
        foreach ($posts->random(50) as $post) {
            $commentCount = rand(1, 5);
            $topComments  = collect();

            for ($c = 0; $c < $commentCount; $c++) {
                $commenter = $allUsers->random();
                $comment = PulseComment::factory()->create([
                    'pulse_post_id' => $post->id,
                    'user_id'       => $commenter->id,
                ]);
                $post->increment('comments_count');
                $topComments->push($comment);
            }

            // Add a few replies
            if ($topComments->isNotEmpty()) {
                $parent = $topComments->first();
                $replier = $allUsers->random();
                PulseComment::factory()->create([
                    'pulse_post_id' => $post->id,
                    'user_id'       => $replier->id,
                    'parent_id'     => $parent->id,
                ]);
                $post->increment('comments_count');
            }

            // Mark one comment as solution on question posts
            if ($post->type === 'question' && $topComments->isNotEmpty()) {
                $topComments->first()->update(['is_solution' => true]);
            }
        }

        // ── 7. Reactions ──────────────────────────────────────────────
        foreach ($posts->random(40) as $post) {
            $reactors = $allUsers->random(rand(1, 8));
            foreach ($reactors as $reactor) {
                PulseReaction::firstOrCreate(
                    ['user_id' => $reactor->id, 'reactable_type' => PulsePost::class, 'reactable_id' => $post->id],
                    ['emoji' => '👍'],
                );
            }
            $post->update(['reactions_count' => $post->reactions()->count()]);
        }

        // ── 8. Follows ─────────────────────────────────────────────────
        foreach ($allUsers->random(10) as $user) {
            $toFollow = $allUsers->where('id', '!=', $user->id)->random(rand(2, 5));
            foreach ($toFollow as $following) {
                PulseFollower::firstOrCreate([
                    'follower_id'  => $user->id,
                    'following_id' => $following->id,
                ]);
            }
        }

        // ── 9. Marketplace items (6) ───────────────────────────────────
        $marketplaceItems = [
            ['title' => 'Fleet Tracker Pro Agent',      'category' => 'agent',      'description' => 'AI agent that monitors your entire fleet in real-time and alerts on anomalies.'],
            ['title' => 'Invoice Automation Workflow',  'category' => 'workflow',   'description' => 'End-to-end invoice processing automation with approval chains.'],
            ['title' => 'Sales Dashboard Template',     'category' => 'dashboard',  'description' => 'Beautiful sales KPI dashboard with 30+ metrics pre-wired.'],
            ['title' => 'Customer Onboarding Template', 'category' => 'template',   'description' => 'Plug-and-play customer onboarding sequence with 12 touchpoints.'],
            ['title' => 'OpenAI Prompt Library',        'category' => 'prompt_library', 'description' => '200+ battle-tested prompts for business automation scenarios.'],
            ['title' => 'WhatsApp Business Integration','category' => 'integration', 'description' => 'Bi-directional WhatsApp integration for customer communication.'],
        ];

        foreach ($marketplaceItems as $item) {
            PulseMarketplaceItem::create([
                'user_id'        => $allUsers->random()->id,
                'title'          => $item['title'],
                'description'    => $item['description'],
                'category'       => $item['category'],
                'version'        => '1.0.' . rand(0, 9),
                'installs_count' => rand(5, 800),
                'avg_rating'     => round(rand(35, 50) / 10, 1),
                'is_published'   => true,
            ]);
        }

        // ── 10. Badges ─────────────────────────────────────────────────
        $badges = [
            ['key' => 'first_post',     'label' => 'First Post',      'icon' => 'edit',           'category' => 'community'],
            ['key' => 'solution_giver', 'label' => 'Problem Solver',  'icon' => 'check_circle',   'category' => 'community'],
            ['key' => 'ai_builder',     'label' => 'AI Builder',      'icon' => 'smart_toy',      'category' => 'expertise'],
            ['key' => 'mentor',         'label' => 'Mentor',          'icon' => 'school',         'category' => 'reputation'],
            ['key' => 'verified_expert','label' => 'Verified Expert', 'icon' => 'verified',       'category' => 'reputation'],
            ['key' => 'streak_7',       'label' => '7-Day Streak',    'icon' => 'local_fire_department', 'category' => 'gamification'],
        ];

        foreach ($badges as $badge) {
            PulseBadge::firstOrCreate(['key' => $badge['key']], array_merge($badge, [
                'description' => "Earned by reaching milestone: {$badge['label']}",
            ]));
        }

        $this->command->info('✓ Pulse seeder complete.');
        $this->command->info("  Admin login: admin@pulse.test / password");
        $this->command->info("  Created: 16 users, 8 communities, 60 posts, ~120 comments");
    }
}
