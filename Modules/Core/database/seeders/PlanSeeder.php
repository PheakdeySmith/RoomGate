<?php

namespace Modules\Core\Database\Seeders;

use App\Models\Plan;
use App\Models\PlanLimit;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Starter',
                'code' => 'starter',
                'price_cents' => 0,
                'currency_code' => 'USD',
                'interval' => 'monthly',
                'is_active' => true,
                'limits' => [
                    'properties_max' => '1',
                    'rooms_max' => '5',
                    'tenant_users_max' => '5',
                    'units_max' => '5',
                    'staff_max' => '0',
                    'analytics' => 'false',
                    'amenities_max' => '10',
                ],
            ],
            [
                'name' => 'Growth',
                'code' => 'growth',
                'price_cents' => 19900,
                'currency_code' => 'USD',
                'interval' => 'monthly',
                'is_active' => true,
                'limits' => [
                    'properties_max' => '10',
                    'rooms_max' => '250',
                    'tenant_users_max' => '50',
                    'units_max' => '250',
                    'staff_max' => '20',
                    'analytics' => 'true',
                    'amenities_max' => '100',
                ],
            ],
            [
                'name' => 'Enterprise',
                'code' => 'enterprise',
                'price_cents' => 99900,
                'currency_code' => 'USD',
                'interval' => 'monthly',
                'is_active' => true,
                'limits' => [
                    'properties_max' => 'unlimited',
                    'rooms_max' => 'unlimited',
                    'tenant_users_max' => 'unlimited',
                    'units_max' => 'unlimited',
                    'staff_max' => 'unlimited',
                    'analytics' => 'true',
                    'amenities_max' => 'unlimited',
                ],
            ],
            [
                'name' => 'Starter',
                'code' => 'starter_yearly',
                'price_cents' => 0,
                'currency_code' => 'USD',
                'interval' => 'yearly',
                'is_active' => true,
                'limits' => [
                    'properties_max' => '1',
                    'rooms_max' => '5',
                    'tenant_users_max' => '5',
                    'units_max' => '5',
                    'staff_max' => '0',
                    'analytics' => 'false',
                    'amenities_max' => '10',
                ],
            ],
            [
                'name' => 'Growth',
                'code' => 'growth_yearly',
                'price_cents' => 214900,
                'currency_code' => 'USD',
                'interval' => 'yearly',
                'is_active' => true,
                'limits' => [
                    'properties_max' => '10',
                    'rooms_max' => '250',
                    'tenant_users_max' => '50',
                    'units_max' => '250',
                    'staff_max' => '20',
                    'analytics' => 'true',
                    'amenities_max' => '100',
                ],
            ],
            [
                'name' => 'Enterprise',
                'code' => 'enterprise_yearly',
                'price_cents' => 1078920,
                'currency_code' => 'USD',
                'interval' => 'yearly',
                'is_active' => true,
                'limits' => [
                    'properties_max' => 'unlimited',
                    'rooms_max' => 'unlimited',
                    'tenant_users_max' => 'unlimited',
                    'units_max' => 'unlimited',
                    'staff_max' => 'unlimited',
                    'analytics' => 'true',
                    'amenities_max' => 'unlimited',
                ],
            ],
        ];

        foreach ($plans as $payload) {
            $limits = $payload['limits'];
            unset($payload['limits']);

            $plan = Plan::updateOrCreate(
                ['code' => $payload['code']],
                $payload
            );

            foreach ($limits as $key => $value) {
                PlanLimit::updateOrCreate(
                    ['plan_id' => $plan->id, 'limit_key' => $key],
                    ['limit_value' => (string) $value]
                );
            }
        }
    }
}
