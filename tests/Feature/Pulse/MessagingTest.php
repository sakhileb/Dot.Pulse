<?php

namespace Tests\Feature\Pulse;

use App\Actions\Pulse\SendMessage;
use App\Models\PulseConversation;
use App\Models\PulseProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class MessagingTest extends TestCase
{
    use RefreshDatabase;

    private User $alice;

    private User $bob;

    protected function setUp(): void
    {
        parent::setUp();
        $this->alice = User::factory()->withPersonalTeam()->create();
        $this->bob = User::factory()->withPersonalTeam()->create();
        PulseProfile::factory()->create(['user_id' => $this->alice->id]);
        PulseProfile::factory()->create(['user_id' => $this->bob->id]);
    }

    public function test_direct_conversation_is_created_between_two_users(): void
    {
        $conv = PulseConversation::directBetween($this->alice->id, $this->bob->id);

        $this->assertDatabaseHas('pulse_conversations', ['id' => $conv->id, 'type' => 'direct']);
        $this->assertDatabaseHas('pulse_conversation_user', ['user_id' => $this->alice->id]);
        $this->assertDatabaseHas('pulse_conversation_user', ['user_id' => $this->bob->id]);
    }

    public function test_direct_between_is_idempotent(): void
    {
        $first = PulseConversation::directBetween($this->alice->id, $this->bob->id);
        $second = PulseConversation::directBetween($this->alice->id, $this->bob->id);

        $this->assertEquals($first->id, $second->id);
        $this->assertDatabaseCount('pulse_conversations', 1);
    }

    public function test_participant_can_send_message(): void
    {
        $conv = PulseConversation::directBetween($this->alice->id, $this->bob->id);

        app(SendMessage::class)->handle(
            conversationId: $conv->id,
            senderId: $this->alice->id,
            body: 'Hello Bob!',
        );

        $this->assertDatabaseHas('pulse_messages', [
            'pulse_conversation_id' => $conv->id,
            'user_id' => $this->alice->id,
            'body' => 'Hello Bob!',
        ]);
    }

    public function test_non_participant_cannot_send_message(): void
    {
        $conv = PulseConversation::directBetween($this->alice->id, $this->bob->id);
        $carol = User::factory()->withPersonalTeam()->create();

        $this->expectException(HttpException::class);

        app(SendMessage::class)->handle(
            conversationId: $conv->id,
            senderId: $carol->id,
            body: 'Unauthorized message',
        );
    }

    public function test_messages_page_requires_authentication(): void
    {
        $this->get('/messages')->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_messages_page(): void
    {
        $this->actingAs($this->alice)
            ->get('/messages')
            ->assertOk();
    }
}
