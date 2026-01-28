<?php

namespace Tests\Feature;

use App\Models\InAppNotification;
use App\Models\OutboundMessage;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminNotificationsOutboundMessagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_and_mark_notifications_and_outbound_messages(): void
    {
        $admin = $this->createPlatformAdmin();

        $notification = InAppNotification::create([
            'user_id' => $admin->id,
            'title' => 'Test Notification',
            'body' => 'Notification body',
            'type' => 'info',
        ]);

        OutboundMessage::create([
            'user_id' => $admin->id,
            'channel' => 'email',
            'subject' => 'Test Email',
            'body' => 'Email body',
            'status' => 'queued',
            'scheduled_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.notifications.index'))
            ->assertOk();

        $this->actingAs($admin)
            ->post(route('admin.notifications.mark-read', ['notification' => $notification->id]))
            ->assertRedirect();

        $this->actingAs($admin)
            ->post(route('admin.notifications.mark-all-read'))
            ->assertRedirect();

        $this->actingAs($admin)
            ->get(route('admin.outbound-messages.index'))
            ->assertOk();
    }

    private function createPlatformAdmin(): User
    {
        Role::create([
            'name' => 'platform_admin',
            'guard_name' => 'web',
        ]);

        $user = User::factory()->create([
            'status' => 'active',
        ]);

        $user->assignRole('platform_admin');

        return $user;
    }
}
