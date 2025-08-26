<?php

namespace App\Console\Commands;

use App\Models\ApprovalState;
use App\Models\Documentation;
use App\Models\StateTransition;
use App\Models\User;
use App\Services\StateTransitionService;
use Illuminate\Console\Command;

class TestStateTransitionsCommand extends Command
{
    protected $signature = 'test:state-transitions';
    
    protected $description = 'Test the new state transition system';

    public function handle(): int
    {
        $this->info('🧪 Testing State Transition System');
        $this->newLine();

        // Verificar que los estados y transiciones existen
        $this->info('📊 Checking States and Transitions...');
        
        $states = ApprovalState::where('model_type', 'App\\Models\\Documentation')->get();
        $this->table(
            ['Name', 'Label', 'Is Initial', 'Is Final', 'Requires Approval', 'Active'],
            $states->map(fn($state) => [
                $state->name,
                $state->label,
                $state->is_initial ? '✅' : '❌',
                $state->is_final ? '✅' : '❌',
                $state->requires_approval ? '✅' : '❌',
                $state->is_active ? '✅' : '❌',
            ])
        );

        $transitions = StateTransition::with(['fromState', 'toState'])->get();
        $this->newLine();
        $this->info('🔄 Available Transitions:');
        $this->table(
            ['From', 'To', 'Name', 'Label', 'Requires Permission', 'Active'],
            $transitions->map(fn($t) => [
                $t->fromState->label,
                $t->toState->label,
                $t->name,
                $t->label,
                $t->requires_permission ? '✅' : '❌',
                $t->is_active ? '✅' : '❌',
            ])
        );

        // Crear un documento de prueba
        $this->newLine();
        $this->info('📝 Creating test documentation...');
        
        $user = User::first();
        if (!$user) {
            $this->error('No users found. Please create a user first.');
            return 1;
        }

        $doc = Documentation::create([
            'title' => 'Test Document - State Transitions',
            'description' => 'This is a test document for the new state transition system',
            'created_by' => $user->id,
        ]);

        $this->info("Created document: {$doc->title} (ID: {$doc->id})");
        $stateName = $doc->state ? $doc->state->getStateName() : 'null';
        $this->info("Initial state: {$stateName}");
        $this->info("Status field: {$doc->status}");

        // Verificar transiciones disponibles
        $stateService = app(StateTransitionService::class);
        $availableTransitions = $stateService->getAvailableTransitions($doc, $user);
        
        $this->newLine();
        $this->info('⚡ Available Transitions:');
        
        if (empty($availableTransitions)) {
            $this->warn('No transitions available');
        } else {
            foreach ($availableTransitions as $transitionData) {
                $transition = $transitionData['transition'];
                $toState = $transitionData['to_state'];
                $this->line("- {$transition->label} ({$transition->name}) → {$toState->label}");
            }
        }

        // Probar transición
        if (!empty($availableTransitions)) {
            $this->newLine();
            $firstTransition = $availableTransitions[0]['transition'];
            $this->info("🚀 Executing transition: {$firstTransition->label}");
            
            $success = $stateService->executeTransition($doc, $firstTransition, $user);
            
            if ($success) {
                $doc->refresh();
                $this->info("✅ Transition executed successfully!");
                $newStateName = $doc->state ? $doc->state->getStateName() : 'null';
                $this->info("New state: {$newStateName}");
                $this->info("New status: {$doc->status}");
            } else {
                $this->error("❌ Transition failed");
            }
        }

        $this->newLine();
        $this->info('🧹 Cleaning up test data...');
        $doc->delete();
        $this->info('✅ Test completed successfully!');

        return 0;
    }
}