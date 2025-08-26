<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('advanced_workflows', function (Blueprint $table) {
            $table->boolean('is_master_workflow')->default(false)->after('is_active');
        });
        
        // Actualizar el fillable en el modelo
        echo "✅ Columna is_master_workflow agregada\n";
    }

    public function down(): void
    {
        Schema::table('advanced_workflows', function (Blueprint $table) {
            $table->dropColumn('is_master_workflow');
        });
    }
};