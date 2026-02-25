<?php

namespace Tests\Feature;

use App\Models\BusinessSetting;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AdminSystemSetupTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_and_update_system_setup(): void
    {
        $admin = $this->createPlatformAdmin();
        BusinessSetting::current();

        $this->actingAs($admin)
            ->get(route('admin.system-setup.index'))
            ->assertRedirect(route('admin.system-setup.section', ['section' => 'general']));

        $this->actingAs($admin)
            ->get(route('admin.system-setup.section', ['section' => 'two-factor']))
            ->assertOk()
            ->assertSee('System Setup')
            ->assertSee('Two Factor Setting');

        $this->actingAs($admin)
            ->put(route('admin.system-setup.utility.update'), [
                'auto_generate_with_invoice' => '1',
                'default_tax_percent' => '5',
                'billing_due_days' => '10',
                'default_unit_currency' => 'USD',
            ])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status', 'Utility settings updated.')
            ->assertRedirect();
    }

    public function test_api_access_setting_blocks_authenticated_api_when_disabled(): void
    {
        $admin = $this->createPlatformAdmin();
        $settings = BusinessSetting::current();
        $settings->putMetaValue('api.enabled', false);
        $settings->save();

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/user')
            ->assertStatus(403);
    }

    public function test_login_redirects_to_two_factor_challenge_when_enabled_for_role(): void
    {
        Mail::fake();

        $admin = $this->createPlatformAdmin();
        $admin->update(['password' => bcrypt('secret123')]);

        $settings = BusinessSetting::current();
        $settings->putMetaValue('two_factor.enabled', true);
        $settings->putMetaValue('two_factor.via_email', true);
        $settings->putMetaValue('two_factor.ttl_minutes', 10);
        $settings->putMetaValue('two_factor.roles', ['platform_admin']);
        $settings->save();

        $this->post(route('login.store'), [
            'email' => $admin->email,
            'password' => 'secret123',
        ])->assertRedirectContains('/two-factor-challenge');
    }

    private function createPlatformAdmin(): User
    {
        Role::query()->firstOrCreate([
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
