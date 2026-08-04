<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Dot.Pulse is Jetstream-teams-enabled, but teams here only attribute
 * *ownership* of shared, publicly-readable content (Community/PulsePost
 * carry a team_id set at create time — see CreateCommunity/CreatePost —
 * and are never read-filtered by team anywhere in this codebase). The
 * genuinely private, per-user state in this schema — "did I RSVP to this
 * event", "did I react to this post", "which marketplace items have I
 * installed" — is owned by a plain user_id column instead, the same
 * single-user shape as Dot.Finance's HasUserScope (commit 2f75bdb).
 *
 * Every model that owns a user_id column AND represents private per-user
 * state (not public feed/catalog content) applies this trait so a query
 * against it is scoped to the authenticated user by default — the goal
 * is that a forgotten where('user_id', ...) call in a future
 * controller/Livewire component can no longer leak another user's rows,
 * because the model itself never returns unscoped results while a user
 * is authenticated.
 *
 * Deliberately NOT applied to PulsePost, PulseComment, PulseReview,
 * PulseProfile, PulseUserBadge, Community, or any other model whose
 * user_id/team_id column only records authorship of content meant to be
 * read across users (the public feed, profiles, reviews, communities) —
 * scoping those would break the product, not secure it. See wiki.md's
 * changelog entry for this pass for the full per-model reasoning.
 *
 * mass-assignment still sets user_id explicitly at create time (see each
 * action/component's create() call); this scope only governs reads.
 */
trait HasUserScope
{
    protected static function bootHasUserScope(): void
    {
        static::addGlobalScope('user', function (Builder $builder): void {
            if (Auth::check()) {
                $builder->where($builder->getModel()->getTable().'.user_id', Auth::id());
            }
        });
    }
}
