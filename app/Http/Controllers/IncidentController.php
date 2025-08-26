<?php

namespace App\Http\Controllers;

use App\Models\Incident;
use App\Models\IncidentComment;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class IncidentController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $userBranchIds = $user->branches->pluck('id');
        
        $query = Incident::with(['user', 'branch', 'assignedTo'])
            ->withCount('comments')
            ->whereIn('branch_id', $userBranchIds);
            
        // Filter by status
        if ($request->status) {
            $query->where('status', $request->status);
        }
        
        // Filter by priority  
        if ($request->priority) {
            $query->where('priority', $request->priority);
        }
        
        // Search
        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }
        
        $incidents = $query->orderBy('created_at', 'desc')
                          ->paginate(12)
                          ->withQueryString();
        
        return Inertia::render('incidents/index', [
            'incidents' => $incidents,
            'filters' => $request->only(['status', 'priority', 'search']),
            'stats' => [
                'total' => Incident::whereIn('branch_id', $userBranchIds)->count(),
                'open' => Incident::whereIn('branch_id', $userBranchIds)->where('status', 'open')->count(),
                'in_progress' => Incident::whereIn('branch_id', $userBranchIds)->where('status', 'in_progress')->count(),
                'resolved' => Incident::whereIn('branch_id', $userBranchIds)->where('status', 'resolved')->count(),
            ]
        ]);
    }
    
    public function show(Incident $incident)
    {
        $user = Auth::user();
        $userBranchIds = $user->branches->pluck('id');
        
        // Check user has access to this incident's branch
        if (!$userBranchIds->contains($incident->branch_id)) {
            abort(403);
        }
        
        $incident->load(['user', 'branch', 'assignedTo', 'comments.user']);
        
        return Inertia::render('incidents/show', [
            'incident' => $incident,
        ]);
    }
    
    public function create()
    {
        $user = Auth::user();
        $branches = $user->branches;
        
        return Inertia::render('incidents/create', [
            'branches' => $branches,
        ]);
    }
    
    public function store(Request $request)
    {
        $user = Auth::user();
        $userBranchIds = $user->branches->pluck('id');
        
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'priority' => 'required|in:low,medium,high,urgent',
            'branch_id' => ['required', Rule::in($userBranchIds)],
            'file' => 'nullable|file|max:25600|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,gif,txt,zip',
        ]);
        
        $data = [
            'title' => $request->title,
            'description' => $request->description,
            'priority' => $request->priority,
            'status' => 'open',
            'user_id' => $user->id,
            'branch_id' => $request->branch_id,
        ];
        
        // Handle file upload
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('incidents', $fileName, 'public');
            
            $data['file_path'] = $filePath;
            $data['file_name'] = $file->getClientOriginalName();
            $data['file_extension'] = $file->getClientOriginalExtension();
            $data['file_size'] = $file->getSize();
            $data['mime_type'] = $file->getMimeType();
        }
        
        $incident = Incident::create($data);
        
        return redirect()->route('incidents.show', $incident)
                        ->with('success', 'Incidencia creada exitosamente.');
    }
    
    public function addComment(Request $request, Incident $incident)
    {
        $user = Auth::user();
        $userBranchIds = $user->branches->pluck('id');
        
        // Check user has access to this incident's branch
        if (!$userBranchIds->contains($incident->branch_id)) {
            abort(403);
        }
        
        $request->validate([
            'comment' => 'required|string',
        ]);
        
        IncidentComment::create([
            'incident_id' => $incident->id,
            'user_id' => $user->id,
            'comment' => $request->comment,
            'is_internal' => false, // User comments are never internal
        ]);
        
        return back()->with('success', 'Comentario agregado exitosamente.');
    }
    
    public function downloadFile(Incident $incident)
    {
        $user = Auth::user();
        $userBranchIds = $user->branches->pluck('id');
        
        // Check user has access to this incident's branch
        if (!$userBranchIds->contains($incident->branch_id)) {
            abort(403);
        }
        
        if (!$incident->hasFile()) {
            abort(404, 'Archivo no encontrado');
        }
        
        return response()->download(
            Storage::disk('public')->path($incident->file_path),
            $incident->file_name ?? 'incidencia.' . $incident->file_extension,
            [
                'Content-Type' => $incident->mime_type ?? 'application/octet-stream',
            ]
        );
    }
}