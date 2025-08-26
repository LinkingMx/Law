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
        Schema::table('documents', function (Blueprint $table) {
            // Archivo principal del documento
            $table->string('file_path')->nullable()->after('description')
                ->comment('Ruta del archivo principal del documento');
            
            // Información del archivo
            $table->string('file_name')->nullable()->after('file_path')
                ->comment('Nombre original del archivo');
                
            $table->string('file_extension')->nullable()->after('file_name')
                ->comment('Extensión del archivo (.pdf, .docx, etc.)');
                
            $table->bigInteger('file_size')->nullable()->after('file_extension')
                ->comment('Tamaño del archivo en bytes');
                
            $table->string('mime_type')->nullable()->after('file_size')
                ->comment('Tipo MIME del archivo');
            
            // Metadatos adicionales
            $table->json('file_metadata')->nullable()->after('mime_type')
                ->comment('Metadatos adicionales del archivo (versión, autor, etc.)');
                
            // Información de carga
            $table->foreignId('uploaded_by')->nullable()->after('file_metadata')
                ->constrained('users')->nullOnDelete()
                ->comment('Usuario que subió el archivo');
                
            $table->timestamp('uploaded_at')->nullable()->after('uploaded_by')
                ->comment('Fecha y hora de carga del archivo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropForeign(['uploaded_by']);
            $table->dropColumn([
                'file_path',
                'file_name', 
                'file_extension',
                'file_size',
                'mime_type',
                'file_metadata',
                'uploaded_by',
                'uploaded_at'
            ]);
        });
    }
};