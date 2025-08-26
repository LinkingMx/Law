<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Documentation;
use App\States\DraftState;
use App\States\PendingApprovalState;
use App\States\ApprovedState;
use App\States\RejectedState;
use App\States\PublishedState;
use App\States\ArchivedState;

return new class extends Migration
{
    public function up(): void
    {
        // Actualizar documentaciones existentes para asignarles estado basado en su campo status
        $documentations = Documentation::whereNull('state')->orWhere('state', '')->get();
        
        foreach ($documentations as $doc) {
            $stateClass = match($doc->status) {
                'draft' => DraftState::class,
                'pending_approval' => PendingApprovalState::class,
                'approved' => ApprovedState::class,
                'rejected' => RejectedState::class,
                'published' => PublishedState::class,
                'archived' => ArchivedState::class,
                default => DraftState::class
            };
            
            $doc->state = $stateClass;
            $doc->save();
        }
    }

    public function down(): void
    {
        // No es necesario revertir
    }
};