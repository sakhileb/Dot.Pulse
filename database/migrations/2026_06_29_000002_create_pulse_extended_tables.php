<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pulse_polls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pulse_post_id')->constrained()->cascadeOnDelete()->unique();
            $table->json('options');
            $table->timestamp('closes_at')->nullable();
            $table->unsignedInteger('votes_count')->default(0);
            $table->timestamps();
        });

        Schema::create('pulse_poll_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pulse_poll_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('option_index');
            $table->timestamps();
            $table->unique(['pulse_poll_id', 'user_id']);
        });

        Schema::create('pulse_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->morphs('mediable');
            $table->string('disk')->default('s3');
            $table->string('path');
            $table->string('mime_type');
            $table->unsignedBigInteger('size')->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('pulse_conversations', function (Blueprint $table) {
            $table->id();
            $table->string('type')->default('direct'); // direct, group
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('pulse_conversation_user', function (Blueprint $table) {
            $table->foreignId('pulse_conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('last_read_at')->nullable();
            $table->primary(['pulse_conversation_id', 'user_id']);
        });

        Schema::create('pulse_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pulse_conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();
            $table->index(['pulse_conversation_id', 'created_at']);
        });

        Schema::create('pulse_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('community_id')->nullable()->constrained('communities')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('type', ['webinar', 'ama', 'launch', 'demo', 'training', 'town_hall', 'meetup'])->default('webinar');
            $table->string('url')->nullable();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->nullable();
            $table->unsignedInteger('rsvps_count')->default(0);
            $table->timestamps();
        });

        Schema::create('pulse_event_rsvps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pulse_event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['going', 'maybe', 'not_going'])->default('going');
            $table->timestamps();
            $table->unique(['pulse_event_id', 'user_id']);
        });

        Schema::create('pulse_badges', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('label');
            $table->string('icon')->nullable();
            $table->string('description')->nullable();
            $table->string('category')->default('community');
            $table->timestamps();
        });

        Schema::create('pulse_user_badges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pulse_badge_id')->constrained()->cascadeOnDelete();
            $table->timestamp('awarded_at');
            $table->timestamps();
            $table->unique(['user_id', 'pulse_badge_id']);
        });

        Schema::create('pulse_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->morphs('reviewable');
            $table->unsignedTinyInteger('rating'); // 1-5
            $table->text('body')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->float('fraud_score')->default(0);
            $table->timestamps();
        });

        Schema::create('pulse_marketplace_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('category', ['agent', 'template', 'dashboard', 'prompt_library', 'automation', 'integration', 'extension', 'workflow'])->default('agent');
            $table->string('version')->default('1.0.0');
            $table->json('changelog')->nullable();
            $table->unsignedInteger('installs_count')->default(0);
            $table->float('avg_rating')->default(0);
            $table->boolean('is_published')->default(false);
            $table->timestamps();
        });

        Schema::create('pulse_marketplace_installs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pulse_marketplace_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['pulse_marketplace_item_id', 'user_id']);
        });

        Schema::create('pulse_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reporter_id')->constrained('users')->cascadeOnDelete();
            $table->morphs('reportable');
            $table->string('reason');
            $table->text('details')->nullable();
            $table->enum('status', ['open', 'under_review', 'resolved', 'dismissed'])->default('open');
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });

        Schema::create('pulse_moderation_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('moderator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->morphs('target');
            $table->string('action'); // approve, reject, flag, remove, warn
            $table->text('rationale')->nullable();
            $table->boolean('is_ai_decision')->default(false);
            $table->timestamps();
        });

        Schema::create('pulse_knowledge_nodes', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type'); // problem, solution, concept, expert, product
            $table->string('label');
            $table->text('description')->nullable();
            $table->json('sources')->nullable();
            $table->unsignedInteger('confidence_score')->default(50);
            $table->timestamps();
        });

        Schema::create('pulse_knowledge_edges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_node_id')->constrained('pulse_knowledge_nodes')->cascadeOnDelete();
            $table->foreignId('to_node_id')->constrained('pulse_knowledge_nodes')->cascadeOnDelete();
            $table->string('relationship');
            $table->float('weight')->default(1.0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pulse_knowledge_edges');
        Schema::dropIfExists('pulse_knowledge_nodes');
        Schema::dropIfExists('pulse_moderation_logs');
        Schema::dropIfExists('pulse_reports');
        Schema::dropIfExists('pulse_marketplace_installs');
        Schema::dropIfExists('pulse_marketplace_items');
        Schema::dropIfExists('pulse_reviews');
        Schema::dropIfExists('pulse_user_badges');
        Schema::dropIfExists('pulse_badges');
        Schema::dropIfExists('pulse_event_rsvps');
        Schema::dropIfExists('pulse_events');
        Schema::dropIfExists('pulse_messages');
        Schema::dropIfExists('pulse_conversation_user');
        Schema::dropIfExists('pulse_conversations');
        Schema::dropIfExists('pulse_media');
        Schema::dropIfExists('pulse_poll_votes');
        Schema::dropIfExists('pulse_polls');
    }
};
