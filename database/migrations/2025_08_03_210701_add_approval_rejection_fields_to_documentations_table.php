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
            // Campos de rechazo
            $table->foreignId('rejected_by')->nullable()->constrained('users')->onDelete('set null')
                ->after('approved_at')->comment('Usuario que rechazó el documento');
            $table->timestamp('rejected_at')->nullable()
                ->after('rejected_by')->comment('Fecha y hora del rechazo');
            $table->text('rejection_reason')->nullable()
                ->after('rejected_at')->comment('Razón del rechazo');
            
            // Campos de nivel de aprobación
            $table->tinyInteger('approval_level')->default(0)
                ->after('rejection_reason')->comment('Nivel actual de aprobación (0-3)');
            
            // Historial de aprobaciones (JSON)
            $table->json('approval_history')->nullable()
                ->after('approval_level')->comment('Historial completo de aprobaciones y rechazos');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documentations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('rejected_by');
            $table->dropColumn([
                'rejected_at',
                'rejection_reason', 
                'approval_level',
                'approval_history'
            ]);
        });
    }
};