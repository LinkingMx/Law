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
        // Backup existing data
        $documents = DB::table('documents')->get();
        $pivotData = DB::table('branch_document')->get();
        
        // Drop the pivot table first
        Schema::dropIfExists('branch_document');
        
        // Drop and recreate documents table with proper structure
        Schema::dropIfExists('documents');
        
        // Recreate documents table with proper primary key
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_category_id')->nullable()->constrained('document_categories');
            $table->string('name');
            $table->text('description')->nullable();
            $table->date('expire_date')->nullable();
            $table->integer('notification_days')->default(30);
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->string('file_extension')->nullable();
            $table->bigInteger('file_size')->nullable();
            $table->string('mime_type')->nullable();
            $table->json('file_metadata')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users');
            $table->timestamp('uploaded_at')->nullable();
            $table->timestamps();
        });
        
        // Recreate pivot table with proper foreign keys
        Schema::create('branch_document', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->foreignId('document_id')->constrained('documents')->onDelete('cascade');
            $table->timestamps();
            
            $table->unique(['branch_id', 'document_id']);
            $table->index('branch_id');
            $table->index('document_id');
        });
        
        // Restore documents data
        foreach ($documents as $document) {
            DB::table('documents')->insert([
                'id' => $document->id,
                'document_category_id' => $document->document_category_id,
                'name' => $document->name,
                'description' => $document->description,
                'expire_date' => $document->expire_date,
                'notification_days' => $document->notification_days ?? 30,
                'file_path' => $document->file_path,
                'file_name' => $document->file_name,
                'file_extension' => $document->file_extension,
                'file_size' => $document->file_size,
                'mime_type' => $document->mime_type,
                'file_metadata' => $document->file_metadata,
                'uploaded_by' => $document->uploaded_by,
                'uploaded_at' => $document->uploaded_at,
                'created_at' => $document->created_at,
                'updated_at' => $document->updated_at,
            ]);
        }
        
        // Restore pivot data
        foreach ($pivotData as $pivot) {
            DB::table('branch_document')->insert([
                'branch_id' => $pivot->branch_id,
                'document_id' => $pivot->document_id,
                'created_at' => $pivot->created_at,
                'updated_at' => $pivot->updated_at,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Store current data
        $documents = DB::table('documents')->get();
        $pivotData = DB::table('branch_document')->get();
        
        // Drop tables
        Schema::dropIfExists('branch_document');
        Schema::dropIfExists('documents');
        
        // Recreate documents with branch_id
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_category_id')->nullable()->constrained('document_categories');
            $table->foreignId('branch_id')->nullable()->constrained('branches');
            $table->string('name');
            $table->text('description')->nullable();
            $table->date('expire_date')->nullable();
            $table->integer('notification_days')->default(30);
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->string('file_extension')->nullable();
            $table->bigInteger('file_size')->nullable();
            $table->string('mime_type')->nullable();
            $table->json('file_metadata')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users');
            $table->timestamp('uploaded_at')->nullable();
            $table->timestamps();
        });
        
        // Restore documents with first branch from pivot
        foreach ($documents as $document) {
            $firstBranch = $pivotData->where('document_id', $document->id)->first();
            
            DB::table('documents')->insert([
                'id' => $document->id,
                'document_category_id' => $document->document_category_id,
                'branch_id' => $firstBranch->branch_id ?? null,
                'name' => $document->name,
                'description' => $document->description,
                'expire_date' => $document->expire_date,
                'notification_days' => $document->notification_days,
                'file_path' => $document->file_path,
                'file_name' => $document->file_name,
                'file_extension' => $document->file_extension,
                'file_size' => $document->file_size,
                'mime_type' => $document->mime_type,
                'file_metadata' => $document->file_metadata,
                'uploaded_by' => $document->uploaded_by,
                'uploaded_at' => $document->uploaded_at,
                'created_at' => $document->created_at,
                'updated_at' => $document->updated_at,
            ]);
        }
    }
};