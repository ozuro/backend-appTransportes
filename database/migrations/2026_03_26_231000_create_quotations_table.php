<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->restrictOnDelete();
            $table->string('quotation_code')->unique();
            $table->string('service_type', 30);
            $table->string('status', 30)->default('pending');
            $table->string('origin_address');
            $table->string('destination_address');
            $table->decimal('estimated_distance_km', 10, 2)->nullable();
            $table->decimal('quoted_amount', 10, 2);
            $table->text('cargo_description')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'service_type']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('quotations');
    }
};
