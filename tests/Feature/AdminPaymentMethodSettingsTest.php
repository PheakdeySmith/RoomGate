<?php

namespace Tests\Feature;

use App\Models\PaymentGatewaySetting;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdminPaymentMethodSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_payment_method_settings(): void
    {
        $admin = $this->createPlatformAdmin();

        $this->actingAs($admin)
            ->get(route('admin.payment-method-settings.index'))
            ->assertOk();

        $stripe = PaymentGatewaySetting::query()->where('gateway_name', 'stripe')->firstOrFail();
        $paypal = PaymentGatewaySetting::query()->where('gateway_name', 'paypal')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('admin.payment-method-settings.active'), [
                'gateways' => [$stripe->id, $paypal->id],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('payment_gateway_settings', [
            'id' => $stripe->id,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.payment-method-settings.update', $stripe->id), [
                'gateway_mode' => 'sandbox',
                'gateway_secret_key' => 'sk_test_123',
                'gateway_publisher_key' => 'pk_test_123',
                'webhook_secret' => 'whsec_test_123',
                'service_charge' => '1',
                'charge_type' => 'P',
                'charge' => '2.50',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('payment_gateway_settings', [
            'id' => $stripe->id,
            'gateway_secret_key' => 'sk_test_123',
            'gateway_publisher_key' => 'pk_test_123',
            'service_charge' => true,
            'charge_type' => 'P',
        ]);
    }

    public function test_admin_can_run_gateway_health_check(): void
    {
        $admin = $this->createPlatformAdmin();

        $stripe = PaymentGatewaySetting::query()->where('gateway_name', 'stripe')->firstOrFail();
        $stripe->update([
            'is_active' => true,
            'gateway_secret_key' => 'sk_test_123',
            'gateway_publisher_key' => 'pk_test_123',
        ]);

        Http::fake([
            'https://api.stripe.com/v1/account' => Http::response(['id' => 'acct_123'], 200),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.payment-method-settings.health-check', ['gatewaySetting' => $stripe->id]))
            ->assertRedirect();

        $this->assertDatabaseHas('payment_gateway_settings', [
            'id' => $stripe->id,
            'health_status' => 'ok',
            'health_message' => 'Stripe credentials valid',
        ]);
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
