<?php

namespace Tests\Feature\Pulse;

use App\Models\PulsePost;
use App\Models\PulseReaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Proves HasUserScope itself is load-bearing, independent of any Policy
 * or explicit where('user_id', ...) call: querying PulseReaction directly
 * as a different user, with no manual scoping anywhere in the path, still
 * cannot see the row. This is the property that makes the scope "defense
 * in depth" rather than decorative — it holds even if a future
 * controller or Livewire component forgets to scope a query explicitly.
 */
class PulseUserScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_scope_alone_blocks_cross_user_access_even_without_an_explicit_where(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $outsider = User::factory()->withPersonalTeam()->create();

        $post = PulsePost::factory()->create(['user_id' => $owner->id]);

        $reaction = PulseReaction::create([
            'user_id' => $owner->id,
            'reactable_type' => PulsePost::class,
            'reactable_id' => $post->id,
            'emoji' => '👍',
        ]);

        $this->actingAs($outsider);

        $this->assertNull(PulseReaction::find($reaction->id));
        $this->assertSame(0, PulseReaction::query()->count());

        $this->actingAs($owner);

        $this->assertNotNull(PulseReaction::find($reaction->id));
        $this->assertSame(1, PulseReaction::query()->count());
    }
}
