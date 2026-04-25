<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('service_settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('transport_service_id')
                ->constrained('transport_services')
                ->cascadeOnDelete();
            $table->foreignId('vehicle_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->foreignId('driver_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->foreignId('recorded_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->decimal('gross_amount', 12, 2)->default(0);
            $table->decimal('company_amount', 12, 2)->default(0);
            $table->decimal('owner_amount', 12, 2)->default(0);
            $table->decimal('driver_amount', 12, 2)->default(0);
            $table->decimal('expense_amount', 12, 2)->default(0);
            $table->string('status', 30)->default('draft');
            $table->timestamp('settled_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['transport_service_id']);
            $table->index(['company_id', 'driver_id', 'status']);
            $table->index(['company_id', 'vehicle_id', 'status']);
            $table->index(['company_id', 'settled_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('service_settlements');
    }
};
