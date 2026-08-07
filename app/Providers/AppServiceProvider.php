<?php

namespace App\Providers;

use App\Models\Community;
use App\Models\PulseComment;
use App\Models\PulsePost;
use App\Policies\CommentPolicy;
use App\Policies\CommunityPolicy;
use App\Policies\PostPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // Policies
        Gate::policy(PulsePost::class, PostPolicy::class);
        Gate::policy(PulseComment::class, CommentPolicy::class);
        Gate::policy(Community::class, CommunityPolicy::class);

        // Rate limiters (used by Livewire components via RateLimiter::attempt())
        RateLimiter::for('pulse-post', fn (Request $req) => Limit::perHour(10)->by($req->user()?->id ?? $req->ip())
        );

        RateLimiter::for('pulse-comment', fn (Request $req) => Limit::perHour(40)->by($req->user()?->id ?? $req->ip())
        );

        RateLimiter::for('pulse-reaction', fn (Request $req) => Limit::perMinute(30)->by($req->user()?->id ?? $req->ip())
        );

        RateLimiter::for('pulse-follow', fn (Request $req) => Limit::perMinute(20)->by($req->user()?->id ?? $req->ip())
        );
    }
}
