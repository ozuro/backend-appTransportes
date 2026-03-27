<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('transport_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->restrictOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained()->nullOnDelete();
            $table->string('service_code')->unique();
            $table->string('service_type', 30);
            $table->string('status', 30)->default('pending');
            $table->string('origin_address');
            $table->string('origin_reference')->nullable();
            $table->string('destination_address');
            $table->string('destination_reference')->nullable();
            $table->timestamp('scheduled_start_at')->nullable();
            $table->timestamp('scheduled_end_at')->nullable();
            $table->timestamp('actual_start_at')->nullable();
            $table->timestamp('actual_end_at')->nullable();
            $table->decimal('quoted_amount', 10, 2)->nullable();
            $table->decimal('final_amount', 10, 2)->nullable();
            $table->string('payment_status', 30)->default('pending');
            $table->text('cargo_description')->nullable();
            $table->text('observations')->nullable();
            $table->text('incidents')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'service_type']);
            $table->index(['company_id', 'scheduled_start_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('transport_services');
    }
};
