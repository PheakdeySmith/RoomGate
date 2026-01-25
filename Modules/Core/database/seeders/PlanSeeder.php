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
                ],
            ],
            [
                'name' => 'Enterprise',
                'code' => 'enterprise',
                'price_cents' => 0,
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
