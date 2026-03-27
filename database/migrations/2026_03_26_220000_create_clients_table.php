<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('client_type', 20)->default('person');
            $table->string('document_type', 20)->nullable();
            $table->string('document_number', 20)->nullable();
            $table->string('name');
            $table->string('business_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('secondary_phone', 30)->nullable();
            $table->string('address')->nullable();
            $table->string('district')->nullable();
            $table->string('province')->nullable();
            $table->string('department')->nullable();
            $table->string('category', 30)->default('occasional');
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'client_type']);
            $table->index(['company_id', 'document_number']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('clients');
    }
};
