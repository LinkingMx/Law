<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Migrate existing branch_id data to the pivot table
        $documents = DB::table('documents')
            ->whereNotNull('branch_id')
            ->get(['id', 'branch_id']);
            
        foreach ($documents as $document) {
            DB::table('branch_document')->insert([
                'branch_id' => $document->branch_id,
                'document_id' => $document->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        
        // Remove the old branch_id column from documents table
        // Use raw SQL for SQLite compatibility
        DB::statement('PRAGMA foreign_keys = OFF');
        
        // Create a new table without branch_id
        DB::statement('CREATE TABLE documents_new AS SELECT 
            id, document_category_id, name, description, expire_date, notification_days,
            file_path, file_name, file_extension, file_size, mime_type, 
            file_metadata, uploaded_by, uploaded_at, created_at, updated_at
            FROM documents');
        
        // Drop old table
        DB::statement('DROP TABLE documents');
        
        // Rename new table
        DB::statement('ALTER TABLE documents_new RENAME TO documents');
        
        DB::statement('PRAGMA foreign_keys = ON');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add back the branch_id column
        Schema::table('documents', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('document_category_id')->constrained();
        });
        
        // Migrate data back from pivot table (only take the first branch if multiple exist)
        $pivotData = DB::table('branch_document')
            ->orderBy('created_at', 'asc')
            ->get();
            
        $documentBranches = [];
        foreach ($pivotData as $pivot) {
            if (!isset($documentBranches[$pivot->document_id])) {
                $documentBranches[$pivot->document_id] = $pivot->branch_id;
            }
        }
        
        foreach ($documentBranches as $documentId => $branchId) {
            DB::table('documents')
                ->where('id', $documentId)
                ->update(['branch_id' => $branchId]);
        }
        
        // Drop the pivot table data (this will be handled by the other migration)
    }
};