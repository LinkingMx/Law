<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Document;
use App\Models\DocumentCategory;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DocumentController extends Controller
{
    public function index(Request $request): Response
    {
        $user = auth()->user();
        
        // Get user's branches through the pivot table
        $branches = $user->branches()->with(['documents'])->get()->map(function ($branch) {
            // Count documents by status
            $allDocuments = $branch->documents;
            $now = now();
            
            return [
                'id' => $branch->id,
                'name' => $branch->name,
                'address' => $branch->address ?? '',
                'phone' => $branch->phone ?? '',
                'email' => $branch->email ?? '',
                'manager' => $branch->manager ?? '',
                'documents_count' => $allDocuments->count(),
                'expiring_soon_count' => $allDocuments->where('expiration_date', '<=', $now->copy()->addDays(30))->where('expiration_date', '>', $now)->count(),
                'expired_count' => $allDocuments->where('expiration_date', '<=', $now)->count(),
                'created_at' => $branch->created_at,
                'updated_at' => $branch->updated_at,
            ];
        });
        
        $selectedBranchId = $request->get('branch');
        $selectedCategoryId = $request->get('category');
        
        $categories = [];
        $documents = [];
        
        if ($selectedBranchId) {
            // Verify user has access to this branch
            $selectedBranch = $branches->firstWhere('id', $selectedBranchId);
            
            if ($selectedBranch) {
                $categories = DocumentCategory::orderBy('name')->get();
                
                if ($selectedCategoryId) {
                    // Load documents for specific category
                    $categoryDocuments = Document::whereHas('branches', function ($query) use ($selectedBranchId) {
                            $query->where('branches.id', $selectedBranchId);
                        })
                        ->where('document_category_id', $selectedCategoryId)
                        ->with('category')
                        ->orderBy('name')
                        ->get();
                    
                    $documents = [$selectedCategoryId => $categoryDocuments];
                } else {
                    // Load documents for all categories
                    $allDocuments = Document::whereHas('branches', function ($query) use ($selectedBranchId) {
                            $query->where('branches.id', $selectedBranchId);
                        })
                        ->with('category')
                        ->orderBy('document_category_id')
                        ->orderBy('name')
                        ->get()
                        ->groupBy('document_category_id');
                    
                    $documents = $allDocuments->toArray();
                }
            }
        }
        
        return Inertia::render('documents', [
            'branches' => $branches,
            'selectedBranchId' => $selectedBranchId ? (int) $selectedBranchId : null,
            'categories' => $categories,
            'documents' => $documents,
        ]);
    }
}