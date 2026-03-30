<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('electronic_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('transport_service_id')->nullable()->constrained('transport_services')->nullOnDelete();
            $table->foreignId('quotation_id')->nullable()->constrained()->nullOnDelete();
            $table->string('document_type', 20);
            $table->string('series', 4);
            $table->unsignedInteger('correlative')->nullable();
            $table->string('status', 20)->default('draft');
            $table->date('issue_date');
            $table->string('currency_code', 3)->default('PEN');
            $table->decimal('subtotal_amount', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->json('payload')->nullable();
            $table->string('xml_path')->nullable();
            $table->string('cdr_path')->nullable();
            $table->string('pdf_path')->nullable();
            $table->string('sunat_ticket')->nullable();
            $table->string('sunat_response_code', 20)->nullable();
            $table->text('sunat_response_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'document_type', 'status']);
            $table->unique(['company_id', 'series', 'correlative']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('electronic_documents');
    }
};
