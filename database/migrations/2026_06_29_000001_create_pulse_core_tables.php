<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pulse_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete()->unique();
            $table->string('headline')->nullable();
            $table->text('bio')->nullable();
            $table->string('avatar_url')->nullable();
            $table->string('cover_url')->nullable();
            $table->string('location')->nullable();
            $table->string('website')->nullable();
            $table->json('skills')->nullable();
            $table->json('expertise_tags')->nullable();
            $table->enum('role', ['customer', 'business', 'enterprise', 'developer', 'partner', 'moderator', 'admin'])->default('customer');
            $table->unsignedInteger('community_points')->default(0);
            $table->unsignedInteger('solutions_accepted')->default(0);
            $table->boolean('is_verified')->default(false);
            $table->timestamps();
        });

        Schema::create('communities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('avatar_url')->nullable();
            $table->string('banner_url')->nullable();
            $table->string('industry')->nullable();
            $table->enum('visibility', ['public', 'private', 'enterprise'])->default('public');
            $table->unsignedInteger('members_count')->default(0);
            $table->timestamps();
        });

        Schema::create('community_memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('community_id')->constrained('communities')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('role', ['member', 'moderator', 'admin'])->default('member');
            $table->timestamps();
            $table->unique(['community_id', 'user_id']);
        });

        Schema::create('community_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('community_id')->constrained('communities')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('pulse_followers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('follower_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('following_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['follower_id', 'following_id']);
        });

        Schema::create('pulse_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('community_id')->nullable()->constrained('communities')->nullOnDelete();
            $table->foreignId('team_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('type', [
                'announcement', 'question', 'idea', 'bug_report', 'release',
                'success_story', 'showcase', 'tutorial', 'agent', 'integration',
                'discussion', 'event', 'article', 'poll', 'video', 'job', 'marketplace',
            ])->default('discussion');
            $table->string('title')->nullable();
            $table->text('body');
            $table->enum('status', ['draft', 'pending', 'published', 'flagged', 'removed'])->default('pending');
            $table->boolean('is_pinned')->default(false);
            $table->unsignedInteger('views_count')->default(0);
            $table->unsignedInteger('comments_count')->default(0);
            $table->unsignedInteger('reactions_count')->default(0);
            $table->float('ai_relevance_score')->default(0.5);
            $table->timestamps();
            $table->index(['status', 'community_id', 'created_at']);
            $table->index(['user_id', 'status']);
        });

        Schema::create('pulse_post_enrichments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pulse_post_id')->constrained()->cascadeOnDelete()->unique();
            $table->text('summary')->nullable();
            $table->json('tags')->nullable();
            $table->enum('sentiment', ['positive', 'neutral', 'negative', 'mixed'])->nullable();
            $table->json('topics')->nullable();
            $table->json('keywords')->nullable();
            $table->string('language', 8)->nullable();
            $table->float('duplicate_score')->default(0);
            $table->float('spam_score')->default(0);
            $table->float('safety_score')->default(1);
            $table->float('business_relevance')->default(0.5);
            $table->float('community_score')->default(0.5);
            $table->enum('moderation_status', ['approved', 'flagged', 'rejected', 'pending'])->default('pending');
            $table->text('moderation_rationale')->nullable();
            $table->timestamps();
        });

        Schema::create('pulse_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pulse_post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('pulse_comments')->nullOnDelete();
            $table->text('body');
            $table->boolean('is_solution')->default(false);
            $table->unsignedInteger('reactions_count')->default(0);
            $table->timestamps();
            $table->index(['pulse_post_id', 'parent_id']);
        });

        Schema::create('pulse_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->morphs('reactable');
            $table->string('emoji')->default('👍');
            $table->timestamps();
            $table->unique(['user_id', 'reactable_type', 'reactable_id']);
        });

        Schema::create('pulse_hashtags', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->unsignedInteger('posts_count')->default(0);
            $table->timestamps();
        });

        Schema::create('pulse_post_hashtag', function (Blueprint $table) {
            $table->foreignId('pulse_post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pulse_hashtag_id')->constrained()->cascadeOnDelete();
            $table->primary(['pulse_post_id', 'pulse_hashtag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pulse_post_hashtag');
        Schema::dropIfExists('pulse_hashtags');
        Schema::dropIfExists('pulse_reactions');
        Schema::dropIfExists('pulse_comments');
        Schema::dropIfExists('pulse_post_enrichments');
        Schema::dropIfExists('pulse_posts');
        Schema::dropIfExists('pulse_followers');
        Schema::dropIfExists('community_rules');
        Schema::dropIfExists('community_memberships');
        Schema::dropIfExists('communities');
        Schema::dropIfExists('pulse_profiles');
    }
};
