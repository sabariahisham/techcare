<?php

namespace App\Http\Controllers\knowledgebase;

use App\Http\Controllers\Controller;
use App\Models\KnowledgeBase;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class KnowledgeBaseController extends Controller
{
    public function index()
    {
        return view('knowledgebase.index');
    }

    public function getData(Request $request)
    {
        if ($request->ajax()) {
            $data = KnowledgeBase::with(['category', 'subCategory', 'creator'])
                ->select('knowledge_base.*');

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function($row) {
                    $btn = '<div class="d-flex align-items-center">';
                    $btn .= '<a href="javascript:void(0);" class="text-body show-record" data-id="'.$row->id.'" title="View Details"><i class="bx bx-show mx-1"></i></a>';
                    $btn .= '<a href="javascript:void(0);" class="text-body edit-record" data-id="'.$row->id.'" title="Edit"><i class="bx bx-edit mx-1"></i></a>';
                    $btn .= '<a href="javascript:void(0);" class="text-body delete-record" data-id="'.$row->id.'" title="Delete"><i class="bx bx-trash mx-1"></i></a>';
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
                ->editColumn('kb_status', function($row) {
                    $statusClass = $row->kb_status == 'aktif' ? 'bg-label-success' : 'bg-label-secondary';
                    $statusText = $row->kb_status == 'aktif' ? 'Active' : 'Inactive';
                    return '<span class="badge '.$statusClass.' text-capitalize">'.$statusText.'</span>';
                })
                ->editColumn('kb_desc', function($row) {
                    return strlen($row->kb_desc) > 100 ? substr($row->kb_desc, 0, 100) . '...' : $row->kb_desc;
                })
                ->rawColumns(['action', 'kb_status'])
                ->make(true);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'kb_title' => 'required|string|max:255',
            'cat_id' => 'required|exists:ref_categories,id',
            'subcat_id' => 'required|exists:ref_sub_categories,id',
            'kb_desc' => 'required|string',
            'kb_status' => 'required|in:aktif,tidak aktif',
            'kb_jenis' => 'required|in:doc_kb,doc_ticket',
            'kb_tag' => 'nullable|string',
            'pdf_files' => 'nullable|array|max:5', // Allow up to 5 files
            'pdf_files.*' => 'nullable|file|mimes:pdf|max:10240' // 10MB max per file
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
            'kb_jenis' => $request->kb_jenis,
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
                    $storageFileName = $knowledgeBaseUuid . '_' . ($index + 1) . '.' . $fileExtension;

                    // Store file in storage/app/public/documents/knowledgebase directory
                    $filePath = $file->storeAs('public/documents/knowledgebase', $storageFileName);

                    // Create document record with all required fields
                    Document::create([
                        'doc_id' => $knowledgeBase->id,
                        'uuid' => Str::uuid(),
                        'doc_mime' => $file->getMimeType(),
                        'doc_name' => $file->getClientOriginalName(),
                        'doc_storage_nm' => $storageFileName,
                        'doc_jenis' => 'doc_kb',
                        'created_by' => Auth::id(),
                        'updated_by' => Auth::id(),
                    ]);
                }
            }
        }

        return response()->json(['success' => true, 'message' => 'Knowledge Base created successfully']);
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
        $knowledgeBase = KnowledgeBase::with(['category', 'subCategory'])->findOrFail($id);

        $data = [
            'id' => $knowledgeBase->id,
            'kb_title' => $knowledgeBase->kb_title,
            'kb_desc' => $knowledgeBase->kb_desc,
            'kb_tag' => $knowledgeBase->kb_tag,
            'kb_status' => $knowledgeBase->kb_status,
            'cat_id' => $knowledgeBase->cat_id,
            'subcat_id' => $knowledgeBase->subcat_id,
        ];

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'kb_title' => 'required|string|max:255',
            'cat_id' => 'required|exists:ref_categories,id',
            'subcat_id' => 'required|exists:ref_sub_categories,id',
            'kb_desc' => 'required|string',
            'kb_status' => 'required|in:aktif,tidak aktif',
            'kb_tag' => 'nullable|string',
            'pdf_files' => 'nullable|array|max:5', // Allow up to 5 files
            'pdf_files.*' => 'nullable|file|mimes:pdf|max:10240' // 10MB max per file
        ]);

        $knowledgeBase = KnowledgeBase::findOrFail($id);
        $knowledgeBase->update([
            'cat_id' => $request->cat_id,
            'subcat_id' => $request->subcat_id,
            'kb_title' => $request->kb_title,
            'kb_desc' => $request->kb_desc,
            'kb_tag' => $request->kb_tag,
            'kb_status' => $request->kb_status,
            'kb_jenis' => $request->kb_jenis,
            'updated_by' => Auth::id(),
        ]);

        // Handle new PDF file uploads (replace all existing documents)
        if ($request->hasFile('pdf_files')) {
            // Delete all existing documents first
            $existingDocuments = Document::where('doc_id', $knowledgeBase->id)
                                       ->where('doc_jenis', 'doc_kb')
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
                    $storageFileName = $knowledgeBase->uuid . '_' . ($index + 1) . '.' . $fileExtension;

                    // Store file in storage/app/public/documents/knowledgebase directory
                    $filePath = $file->storeAs('public/documents/knowledgebase', $storageFileName);

                    // Create document record with all required fields
                    Document::create([
                        'doc_id' => $knowledgeBase->id,
                        'uuid' => Str::uuid(),
                        'doc_mime' => $file->getMimeType(),
                        'doc_name' => $file->getClientOriginalName(),
                        'doc_storage_nm' => $storageFileName,
                        'doc_jenis' => 'doc_kb',
                        'created_by' => Auth::id(),
                        'updated_by' => Auth::id(),
                    ]);
                }
            }
        }

        return response()->json(['success' => true, 'message' => 'Knowledge Base updated successfully']);
    }

    public function destroy($id)
    {
        $knowledgeBase = KnowledgeBase::with('documents')->findOrFail($id);

        // Delete associated files from storage and document records
        foreach ($knowledgeBase->documents as $document) {
            // Delete file from storage
            if (Storage::exists('public/documents/knowledgebase/' . $document->doc_storage_nm)) {
                Storage::delete('public/documents/knowledgebase/' . $document->doc_storage_nm);
            }
            // Delete document record
            $document->delete();
        }

        // Delete knowledge base record
        $knowledgeBase->delete();

        return response()->json(['success' => true, 'message' => 'Knowledge Base deleted successfully']);
    }

    public function deleteDocument($documentId)
    {
        $document = Document::findOrFail($documentId);

        // Delete file from storage
        if (Storage::exists('public/documents/knowledgebase/' . $document->doc_storage_nm)) {
            Storage::delete('public/documents/knowledgebase/' . $document->doc_storage_nm);
        }

        // Delete document record
        $document->delete();

        return response()->json(['success' => true, 'message' => 'Document deleted successfully']);
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
}
