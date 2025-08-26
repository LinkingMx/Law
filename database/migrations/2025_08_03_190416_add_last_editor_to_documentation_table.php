<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('documentations', function (Blueprint $table) {
            $table->unsignedBigInteger('last_edited_by')->nullable()->after('approved_at');
            $table->timestamp('last_edited_at')->nullable()->after('last_edited_by');
            
            $table->foreign('last_edited_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documentations', function (Blueprint $table) {
            $table->dropForeign(['last_edited_by']);
            $table->dropColumn(['last_edited_by', 'last_edited_at']);
        });
    }
};