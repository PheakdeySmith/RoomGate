<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('amenity_room', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('room_id');
            $table->unsignedBigInteger('amenity_id');

            $table->primary(['tenant_id', 'room_id', 'amenity_id']);
            $table->index(['tenant_id', 'room_id'], 'idx_ar_room');
            $table->index(['tenant_id', 'amenity_id'], 'idx_ar_amenity');

            $table->foreign('tenant_id')->references('id')->on('tenants')->restrictOnDelete();
            $table->foreign(['room_id', 'tenant_id'], 'fk_ar_room_tenant')
                ->references(['id', 'tenant_id'])->on('rooms')
                ->cascadeOnDelete();
            $table->foreign(['amenity_id', 'tenant_id'], 'fk_ar_amenity_tenant')
                ->references(['id', 'tenant_id'])->on('amenities')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('amenity_room');
    }
};
