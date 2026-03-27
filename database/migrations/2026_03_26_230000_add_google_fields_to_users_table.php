<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('firebase_uid')->nullable()->unique()->after('document_number');
            $table->string('avatar_url')->nullable()->after('firebase_uid');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['firebase_uid', 'avatar_url']);
        });
    }
};
