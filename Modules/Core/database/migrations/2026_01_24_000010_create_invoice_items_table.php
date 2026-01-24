<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('invoice_id');

            $table->string('description', 255);
            $table->bigInteger('amount_cents'); // signed, negative allowed

            $table->string('item_type', 20)->default('other'); // rent|deposit|utility|amenity|fee|discount|other

            $table->string('ref_table', 64)->nullable();
            $table->unsignedBigInteger('ref_id')->nullable();

            $table->timestampsTz(3);
            $table->softDeletesTz('deleted_at', 3);

            $table->index(['tenant_id', 'invoice_id'], 'idx_ii_invoice');
            $table->index(['ref_table', 'ref_id'], 'idx_ii_ref');
            $table->index(['tenant_id', 'deleted_at'], 'idx_ii_tenant_deleted');

            $table->foreign('tenant_id')->references('id')->on('tenants')->restrictOnDelete();
            $table->foreign('invoice_id')->references('id')->on('invoices')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};
