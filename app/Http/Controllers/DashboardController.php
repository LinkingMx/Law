<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = auth()->user();
        
        // Get user's branches with document counts
        $branches = $user->branches()->with(['documents'])->get();
        
        // Calculate overall statistics across all branches
        $allDocuments = $branches->flatMap(function ($branch) {
            return $branch->documents;
        });
        
        $now = now();
        
        $statistics = [
            'total_branches' => $branches->count(),
            'total_documents' => $allDocuments->count(),
            'documents_vigent' => $allDocuments->where('expiration_date', '>', $now)->count(),
            'documents_expiring_soon' => $allDocuments->where('expiration_date', '<=', $now->copy()->addDays(30))->where('expiration_date', '>', $now)->count(),
            'documents_expired' => $allDocuments->where('expiration_date', '<=', $now)->count(),
        ];
        
        // Recent activity - get latest documents from user's branches
        $recentDocuments = Document::whereIn('branch_id', $branches->pluck('id'))
            ->with(['branch', 'category'])
            ->latest()
            ->limit(5)
            ->get();
            
        // Branch summary with document stats
        $branchSummary = $branches->map(function ($branch) use ($now) {
            $branchDocuments = $branch->documents;
            
            return [
                'id' => $branch->id,
                'name' => $branch->name,
                'address' => $branch->address,
                'documents_count' => $branchDocuments->count(),
                'expiring_soon_count' => $branchDocuments->where('expiration_date', '<=', $now->copy()->addDays(30))->where('expiration_date', '>', $now)->count(),
                'expired_count' => $branchDocuments->where('expiration_date', '<=', $now)->count(),
            ];
        });
        
        return Inertia::render('dashboard', [
            'statistics' => $statistics,
            'branchSummary' => $branchSummary,
            'recentDocuments' => $recentDocuments,
        ]);
    }
}