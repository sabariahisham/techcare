<?php

namespace App\Http\Controllers\knowledgebase;

use App\Http\Controllers\Controller;
use App\Models\KnowledgeBase;
use App\Models\Document;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class KnowledgeBaseController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $hasRole = $user && $user->roles->isNotEmpty();

        return view('knowledgebase.index', compact('hasRole'));
    }

    public function getData(Request $request)
    {
        if ($request->ajax()) {
            $data = KnowledgeBase::with(['category', 'subCategory', 'creator.roles', 'updater'])
                ->select('knowledge_base.*');

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function($row) {
                    $user = Auth::user();
                    $canEditDelete = $user && ($user->hasRole('Administrator') || $user->id === $row->created_by);

                    $btn = '<div class="d-flex align-items-center">';
                    // View button - always visible to all users
                    $btn .= '<a href="javascript:void(0);" class="text-body show-record" data-id="'.$row->id.'" title="View Details"><i class="bx bx-show mx-1"></i></a>';

                    // Edit and Delete buttons - only visible to Administrator or the article's author
                    if ($canEditDelete) {
                        $btn .= '<a href="javascript:void(0);" class="text-body edit-record" data-id="'.$row->id.'" title="Edit"><i class="bx bx-edit mx-1"></i></a>';
                        $btn .= '<a href="javascript:void(0);" class="text-body delete-record" data-id="'.$row->id.'" data-title="'.htmlspecialchars($row->kb_title, ENT_QUOTES, 'UTF-8').'" title="Delete"><i class="bx bx-trash mx-1"></i></a>';
                    }

                    $btn .= '</div>';
                    return $btn;
                })
                ->addColumn('category_name', function($row) {
                    return $row->category ? $row->category->cat_name : '-';
                })
                ->addColumn('subcategory_name', function($row) {
                    return $row->subCategory ? $row->subCategory->subcat_name : '-';
                })
                ->addColumn('created_by', function($row) {
                    if ($row->creator) {
                        return $row->creator->nama;
                    }
                    return $row->created_by ? 'Unknown User (ID: ' . $row->created_by . ')' : '-';
                })
                ->editColumn('created_at', function($row) {
                    return $row->created_at ? $row->created_at->format('d/m/Y H:i') : '-';
                })
                ->addColumn('updated_by_name', function($row) {
                    if ($row->updater) {
                        return $row->updater->nama;
                    }
                    return $row->updated_by ? 'Unknown User (ID: ' . $row->updated_by . ')' : '-';
                })
                ->editColumn('updated_at', function($row) {
                    return $row->updated_at ? $row->updated_at->format('d/m/Y H:i') : '-';
                })
                ->addColumn('creator_roles', function($row) {
                    if (!$row->creator) {
                        return '-';
                    }
                    $roles = $row->creator->roles->pluck('name');
                    if ($roles->isEmpty()) {
                        return '<span class="badge bg-label-secondary">Tiada Peranan</span>';
                    }
                    return $roles->map(function($role) {
                        return '<span class="badge bg-label-primary me-1">' . htmlspecialchars($role) . '</span>';
                    })->implode('');
                })
                ->editColumn('kb_status', function($row) {
                    $statusClass = $row->kb_status == 'aktif' ? 'bg-label-success' : 'bg-label-secondary';
                    $statusText = $row->kb_status == 'aktif' ? 'Active' : 'Inactive';
                    return '<span class="badge '.$statusClass.' text-capitalize">'.$statusText.'</span>';
                })
                ->editColumn('kb_desc', function($row) {
                    return strlen($row->kb_desc) > 100 ? substr($row->kb_desc, 0, 100) . '...' : $row->kb_desc;
                })
                ->addColumn('kb_tag_formatted', function($row) {
                    if (empty($row->kb_tag)) {
                        return '<span class="badge bg-label-secondary">Tiada Tag</span>';
                    }

                    // Split tags by comma and create badges
                    $tags = explode(',', $row->kb_tag);
                    $tagBadges = '';

                    foreach ($tags as $tag) {
                        $tag = trim($tag);
                        if (!empty($tag)) {
                            $tagBadges .= '<span class="badge bg-label-primary me-1 mb-1">' . htmlspecialchars($tag) . '</span>';
                        }
                    }

                    return $tagBadges ?: '<span class="badge bg-label-secondary">Tiada Tag</span>';
                })
                ->rawColumns(['action', 'kb_status', 'kb_tag_formatted', 'creator_roles'])
                ->make(true);
        }
    }

    public function store(Request $request)
    {
        // Check if user has a role
        $user = Auth::user();
        if (!$user || $user->roles->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak mempunyai kebenaran untuk menambah artikel. Hanya pengguna dengan peranan yang ditetapkan boleh menambah artikel.'
            ], 403);
        }

        $request->validate([
            'kb_title' => 'required|string|max:255',
            'cat_id' => 'required|exists:ref_categories,id',
            'subcat_id' => 'required|exists:ref_sub_categories,id',
            'kb_desc' => 'required|string',
            'kb_status' => 'required|in:aktif,tidak aktif',
            // 'kb_jenis' => 'required|in:doc_kb,doc_ticket',
            'kb_tag' => 'nullable|string',
            'pdf_files' => 'nullable|array|max:5', // Allow up to 5 files
            'pdf_files.*' => 'nullable|file|mimes:pdf|max:2048' // 2MB max per file
        ]);

        // Create knowledge base entry
        $knowledgeBaseUuid = Str::uuid();
        $knowledgeBase = KnowledgeBase::create([
            'uuid' => $knowledgeBaseUuid,
            'cat_id' => $request->cat_id,
            'subcat_id' => $request->subcat_id,
            'kb_title' => $request->kb_title,
            'kb_desc' => $request->kb_desc,
            'kb_tag' => $request->kb_tag,
            'kb_status' => $request->kb_status,
            // 'kb_jenis' => $request->kb_jenis,
            'created_by' => Auth::id(),
        ]);

        // Handle multiple PDF file uploads
        if ($request->hasFile('pdf_files')) {
            $files = $request->file('pdf_files');

            // Process each uploaded file
            foreach ($files as $index => $file) {
                if ($file->isValid()) {
                    // Generate unique storage name using knowledge base UUID and file index
                    $fileExtension = $file->getClientOriginalExtension();

                    // Ensure unique filename by checking existing documents (including soft-deleted ones)
                    $storageFileName = $this->generateUniqueStorageFileName($knowledgeBaseUuid, $index + 1, $fileExtension);

                    // Store file in storage/app/public/documents/knowledgebase directory
                    $filePath = $file->storeAs('public/documents/knowledgebase', $storageFileName);

                    // Create document record with all required fields
                    Document::create([
                        'doc_id' => $knowledgeBase->id,
                        'uuid' => Str::uuid(),
                        'doc_mime' => $file->getMimeType(),
                        'doc_name' => $file->getClientOriginalName(),
                        'doc_storage_nm' => $storageFileName,
                        'doc_jenis' => 'KB',
                        'created_by' => Auth::id(),
                        'updated_by' => Auth::id(),
                    ]);
                }
            }
        }

        return response()->json(['success' => true]);
    }

    public function show($id)
    {
        $knowledgeBase = KnowledgeBase::with(['category', 'subCategory', 'creator', 'documents'])->findOrFail($id);

        $data = [
            'id' => $knowledgeBase->id,
            'uuid' => $knowledgeBase->uuid,
            'kb_title' => $knowledgeBase->kb_title,
            'kb_desc' => $knowledgeBase->kb_desc,
            'kb_tag' => $knowledgeBase->kb_tag,
            'kb_status' => $knowledgeBase->kb_status,
            'category_name' => $knowledgeBase->category ? $knowledgeBase->category->cat_name : null,
            'subcategory_name' => $knowledgeBase->subCategory ? $knowledgeBase->subCategory->subcat_name : null,
            'created_by' => $knowledgeBase->creator ? $knowledgeBase->creator->nama : null,
            'created_at' => $knowledgeBase->created_at,
            'documents' => $knowledgeBase->documents->map(function($doc) {
                return [
                    'id' => $doc->id,
                    'uuid' => $doc->uuid,
                    'doc_name' => $doc->doc_name,
                    'doc_mime' => $doc->doc_mime,
                    'created_at' => $doc->created_at,
                ];
            }),
        ];

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function edit($id)
    {
        $user = Auth::user();
        $knowledgeBase = KnowledgeBase::with(['category', 'subCategory', 'documents'])->findOrFail($id);

        if (!$user || (!$user->hasRole('Administrator') && $user->id !== $knowledgeBase->created_by)) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak mempunyai kebenaran untuk mengedit artikel ini. Hanya pentadbir atau penulis artikel boleh mengedit artikel.'
            ], 403);
        }

        $data = [
            'id' => $knowledgeBase->id,
            'kb_title' => $knowledgeBase->kb_title,
            'kb_desc' => $knowledgeBase->kb_desc,
            'kb_tag' => $knowledgeBase->kb_tag,
            'kb_status' => $knowledgeBase->kb_status,
            'cat_id' => $knowledgeBase->cat_id,
            'subcat_id' => $knowledgeBase->subcat_id,
            'documents' => $knowledgeBase->documents->map(function($doc) {
                return [
                    'id' => $doc->id,
                    'uuid' => $doc->uuid,
                    'doc_name' => $doc->doc_name,
                    'doc_mime' => $doc->doc_mime,
                    'created_at' => $doc->created_at,
                ];
            }),
        ];

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function update(Request $request, $id)
    {
        $user = Auth::user();
        $knowledgeBase = KnowledgeBase::findOrFail($id);

        if (!$user || (!$user->hasRole('Administrator') && $user->id !== $knowledgeBase->created_by)) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak mempunyai kebenaran untuk mengedit artikel ini. Hanya pentadbir atau penulis artikel boleh mengedit artikel.'
            ], 403);
        }

        $request->validate([
            'kb_title' => 'required|string|max:255',
            'cat_id' => 'required|exists:ref_categories,id',
            'subcat_id' => 'required|exists:ref_sub_categories,id',
            'kb_desc' => 'required|string',
            'kb_status' => 'required|in:aktif,tidak aktif',
            'kb_tag' => 'nullable|string',
            'pdf_files' => 'nullable|array|max:5', // Allow up to 5 files
            'pdf_files.*' => 'file|mimes:pdf|max:2048' // 2MB max per file
        ]);

        $knowledgeBase->update([
            'cat_id' => $request->cat_id,
            'subcat_id' => $request->subcat_id,
            'kb_title' => $request->kb_title,
            'kb_desc' => $request->kb_desc,
            'kb_tag' => $request->kb_tag,
            'kb_status' => $request->kb_status,
            'updated_by' => Auth::id(),
        ]);

        // Handle new PDF file uploads (replace all existing documents)
        if ($request->hasFile('pdf_files')) {
            // Delete all existing documents first
            $existingDocuments = Document::where('doc_id', $knowledgeBase->id)
                                       ->where('doc_jenis', 'KB')
                                       ->get();

            foreach ($existingDocuments as $existingDocument) {
                // Delete file from storage
                if (Storage::exists('public/documents/knowledgebase/' . $existingDocument->doc_storage_nm)) {
                    Storage::delete('public/documents/knowledgebase/' . $existingDocument->doc_storage_nm);
                }
                // Delete document record
                $existingDocument->delete();
            }

            // Process each uploaded file
            $files = $request->file('pdf_files');
            foreach ($files as $index => $file) {
                if ($file->isValid()) {
                    // Generate unique storage name using knowledge base UUID and file index
                    $fileExtension = $file->getClientOriginalExtension();

                    // Ensure unique filename by checking existing documents (including soft-deleted ones)
                    $storageFileName = $this->generateUniqueStorageFileName($knowledgeBase->uuid, $index + 1, $fileExtension);

                    // Store file in storage/app/public/documents/knowledgebase directory
                    $filePath = $file->storeAs('public/documents/knowledgebase', $storageFileName);

                    // Create document record with all required fields
                    Document::create([
                        'doc_id' => $knowledgeBase->id,
                        'uuid' => Str::uuid(),
                        'doc_mime' => $file->getMimeType(),
                        'doc_name' => $file->getClientOriginalName(),
                        'doc_storage_nm' => $storageFileName,
                        'doc_jenis' => 'KB',
                        'created_by' => Auth::id(),
                        'updated_by' => Auth::id(),
                    ]);
                }
            }
        }

        return response()->json(['success' => true]);
    }

    public function destroy($id)
    {
        $user = Auth::user();
        $knowledgeBase = KnowledgeBase::with('documents')->findOrFail($id);

        if (!$user || (!$user->hasRole('Administrator') && $user->id !== $knowledgeBase->created_by)) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak mempunyai kebenaran untuk menghapus artikel ini. Hanya pentadbir atau penulis artikel boleh menghapus artikel.'
            ], 403);
        }

        // Delete associated files from storage only (keep database records)
        foreach ($knowledgeBase->documents as $document) {
            // Delete file from storage
            if (Storage::exists('public/documents/knowledgebase/' . $document->doc_storage_nm)) {
                Storage::delete('public/documents/knowledgebase/' . $document->doc_storage_nm);
            }
            // Note: Document record is kept in database
        }

        // Note: Knowledge base record is kept in database

        return response()->json(['success' => true]);
    }

    /**
     * Get Aduan Selesai (resolved tickets) for DataTable
     */
    public function getAduanSelesaiData(Request $request)
    {
        if ($request->ajax()) {
            $query = Ticket::with([
                'user',
                'category',
                'subCategory',
                'chronologies.escalatedTo',
            ])->where('tick_status', 6)
              ->orderBy('created_at', 'desc');

            return DataTables::eloquent($query)
                ->addIndexColumn()
                ->addColumn('no_log_tiket', function ($ticket) {
                    return $ticket->ticket_nolog ?? 'N/A';
                })
                ->addColumn('tarikh_aduan', function ($ticket) {
                    return $ticket->created_at ? $ticket->created_at->format('d/m/Y H:i') : '-';
                })
                ->addColumn('nama_pengadu', function ($ticket) {
                    return $ticket->user->nama ?? 'N/A';
                })
                ->addColumn('kategori', function ($ticket) {
                    return $ticket->category->cat_name ?? 'N/A';
                })
                ->addColumn('sub_kategori', function ($ticket) {
                    return $ticket->subCategory->subcat_name ?? 'N/A';
                })
                ->addColumn('status', function ($ticket) {
                    return '<span class="badge bg-success">Aduan Selesai</span>';
                })
                ->addColumn('pegawai_tugasan', function ($ticket) {
                    $latestChronology = $ticket->chronologies()
                        ->whereNotNull('escalate_to')
                        ->where('tick_status', 6)
                        ->with('escalatedTo')
                        ->latest()
                        ->first();

                    if (!$latestChronology) {
                        $latestChronology = $ticket->chronologies()
                            ->whereNotNull('escalate_to')
                            ->with('escalatedTo')
                            ->latest()
                            ->first();
                    }

                    if ($latestChronology && $latestChronology->escalatedTo) {
                        return $latestChronology->escalatedTo->nama;
                    }
                    return 'Belum Ditugaskan';
                })
                ->filterColumn('no_log_tiket', function ($query, $keyword) {
                    $query->where('ticket_nolog', 'like', "%{$keyword}%");
                })
                ->filterColumn('tarikh_aduan', function ($query, $keyword) {
                    $query->whereRaw("DATE_FORMAT(created_at, '%d/%m/%Y %H:%i') like ?", ["%{$keyword}%"]);
                })
                ->filterColumn('nama_pengadu', function ($query, $keyword) {
                    $query->whereHas('user', function ($q) use ($keyword) {
                        $q->where('nama', 'like', "%{$keyword}%");
                    });
                })
                ->filterColumn('kategori', function ($query, $keyword) {
                    $query->whereHas('category', function ($q) use ($keyword) {
                        $q->where('cat_name', 'like', "%{$keyword}%");
                    });
                })
                ->filterColumn('sub_kategori', function ($query, $keyword) {
                    $query->whereHas('subCategory', function ($q) use ($keyword) {
                        $q->where('subcat_name', 'like', "%{$keyword}%");
                    });
                })
                ->addColumn('uuid', function ($ticket) {
                    return $ticket->uuid;
                })
                ->rawColumns(['status'])
                ->make(true);
        }
    }

    /**
     * Get article details for confirmation dialog
     */
    public function getDetails($id)
    {
        $knowledgeBase = KnowledgeBase::with('documents')->findOrFail($id);

        $documents = $knowledgeBase->documents->map(function($doc) {
            return [
                'id' => $doc->id,
                'doc_name' => $doc->doc_name,
                'name' => $doc->doc_name, // alias for frontend compatibility
                'doc_mime' => $doc->doc_mime,
                'doc_storage_nm' => $doc->doc_storage_nm,
            ];
        });

        return response()->json([
            'success' => true,
            'kb_title' => $knowledgeBase->kb_title,
            'documents' => $documents,
        ]);
    }

    /**
     * Soft delete knowledge base with option to permanently delete documents from storage
     */
    public function softDelete(Request $request, $id)
    {
        $user = Auth::user();
        $knowledgeBase = KnowledgeBase::with('documents')->findOrFail($id);

        if (!$user || (!$user->hasRole('Administrator') && $user->id !== $knowledgeBase->created_by)) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak mempunyai kebenaran untuk menghapus artikel ini. Hanya pentadbir atau penulis artikel boleh menghapus artikel.'
            ], 403);
        }

        $unlinkFiles = $request->boolean('unlink_files', false);
        $deletedDocuments = 0;
        $unlinkedFiles = 0;

        // Handle documents
        foreach ($knowledgeBase->documents as $document) {
            if ($unlinkFiles) {
                // Permanently delete files from storage
                if (Storage::exists('public/documents/knowledgebase/' . $document->doc_storage_nm)) {
                    Storage::delete('public/documents/knowledgebase/' . $document->doc_storage_nm);
                    $unlinkedFiles++;
                }
                // Force delete document record (permanent delete)
                $document->delete();
            } else {
                // Soft delete document record (files remain in storage)
                $document->delete();
            }
            $deletedDocuments++;
        }

        // Soft delete knowledge base record
        $knowledgeBase->delete();

        return response()->json([
            'success' => true,
            'deleted_documents' => $deletedDocuments,
            'unlinked_files' => $unlinkedFiles
        ]);
    }

    public function deleteDocument($documentId)
    {
        $document = Document::where('id', $documentId)
                          ->where('doc_jenis', 'KB')
                          ->firstOrFail();

        // Optional: Add authorization check here if needed
        // $this->authorize('update', $document->knowledgeBase);

        // Delete file from storage
        if (Storage::exists('public/documents/knowledgebase/' . $document->doc_storage_nm)) {
            Storage::delete('public/documents/knowledgebase/' . $document->doc_storage_nm);
        }

        // Delete document record
        $document->delete();

        return response()->json(['success' => true]);
    }

    public function downloadDocument($documentId)
    {
        $document = Document::findOrFail($documentId);

        $filePath = storage_path('app/public/documents/knowledgebase/' . $document->doc_storage_nm);

        if (!file_exists($filePath)) {
            return response()->json(['error' => 'File not found'], 404);
        }

        return response()->download($filePath, $document->doc_name);
    }

    /**
     * Generate a unique storage filename by checking existing documents
     * (including soft-deleted ones) to avoid constraint violations
     */
    private function generateUniqueStorageFileName($uuid, $initialIndex, $extension)
    {
        $index = $initialIndex;
        $maxAttempts = 100; // Prevent infinite loops
        $attempts = 0;

        do {
            $storageFileName = $uuid . '_' . $index . '.' . $extension;

            // Check if this filename already exists (including soft-deleted records)
            $exists = Document::withTrashed()
                ->where('doc_storage_nm', $storageFileName)
                ->exists();

            if (!$exists) {
                return $storageFileName;
            }

            $index++;
            $attempts++;

        } while ($attempts < $maxAttempts);

        // Fallback: use timestamp if we can't find a unique name
        return $uuid . '_' . time() . '_' . $initialIndex . '.' . $extension;
    }
}
