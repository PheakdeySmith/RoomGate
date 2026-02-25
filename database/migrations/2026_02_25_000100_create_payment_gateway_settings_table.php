<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_gateway_settings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('gateway_name', 50)->unique();
            $table->boolean('is_active')->default(false);

            $table->string('gateway_username', 191)->nullable();
            $table->string('gateway_password', 191)->nullable();
            $table->string('gateway_signature', 191)->nullable();
            $table->string('gateway_client_id', 191)->nullable();
            $table->string('gateway_mode', 20)->default('sandbox');
            $table->string('gateway_secret_key', 191)->nullable();
            $table->string('gateway_publisher_key', 191)->nullable();
            $table->string('gateway_private_key', 191)->nullable();

            $table->string('merchant_id', 191)->nullable();
            $table->string('webhook_secret', 191)->nullable();

            $table->boolean('service_charge')->default(false);
            $table->char('charge_type', 1)->nullable()->comment('P=percentage, F=flat');
            $table->decimal('charge', 10, 2)->default(0);

            $table->timestampsTz(3);
        });

        DB::table('payment_gateway_settings')->insert([
            ['gateway_name' => 'paypal', 'gateway_mode' => 'sandbox', 'is_active' => false, 'created_at' => now(), 'updated_at' => now()],
            ['gateway_name' => 'stripe', 'gateway_mode' => 'sandbox', 'is_active' => false, 'created_at' => now(), 'updated_at' => now()],
            ['gateway_name' => 'bakong', 'gateway_mode' => 'sandbox', 'is_active' => false, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_gateway_settings');
    }
};
