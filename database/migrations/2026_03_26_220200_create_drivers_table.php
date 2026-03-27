<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assigned_vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('document_type', 20)->nullable();
            $table->string('document_number', 20)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('license_number', 30)->unique();
            $table->string('license_category', 10)->nullable();
            $table->date('license_expires_at')->nullable();
            $table->string('status', 30)->default('available');
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'document_number']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('drivers');
    }
};
