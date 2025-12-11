<?php

/*
 * Function TicketAttachmentController.php:
 *  - Manage attachments related to tickets.
 *  - Handle file uploads, downloads, and deletions.
 *  - Provide attachment functionalities for Pengadu and Pegawai Tugasan.
 */

namespace App\Http\Controllers\Ticket;

use App\Models\Document;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class TicketAttachmentController extends Controller
{
    /**
     * Upload multiple files for a ticket
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function upload(Request $request)
    {
        $request->validate([
            'ticket_id' => 'required|exists:tickets,id',
            'attachments.*' => 'required|file|max:10240|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,txt,jpg,jpeg,png,gif'
        ], [
            'attachments.*.max' => 'Setiap fail tidak boleh melebihi 10MB.',
            'attachments.*.mimes' => 'Jenis fail yang dibenarkan: PDF, Word, Excel, PowerPoint, Text, dan gambar (JPG, PNG, GIF).'
        ]);

        try {
            $uploadedFiles = [];
            $files = $request->file('attachments');

            if (!is_array($files)) {
                $files = [$files];
            }

            foreach ($files as $file) {
                if ($file && $file->isValid()) {
                    // Generate unique filename
                    $originalName = $file->getClientOriginalName();
                    $extension = $file->getClientOriginalExtension();
                    $filename = time() . '_' . uniqid() . '.' . $extension;

                    // Store file
                    $path = $file->storeAs('ticket_attachments', $filename, 'public');

                    // Create document record
                    $document = Document::create([
                        'doc_id' => $request->ticket_id,
                        'uuid' => Str::uuid(),
                        'doc_mime' => $file->getMimeType(),
                        'doc_name' => $originalName,
                        'doc_storage_nm' => $filename,
                        'doc_jenis' => 'TICKET',
                        'created_by' => Auth::id(),
                        'updated_by' => Auth::id(),
                    ]);

                    $uploadedFiles[] = [
                        'id' => $document->id,
                        'name' => $originalName,
                        'size' => $file->getSize(),
                        'type' => $file->getMimeType()
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'message' => count($uploadedFiles) . ' fail berjaya dimuat naik.',
                'files' => $uploadedFiles
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ralat semasa memuat naik fail: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete an attachment
     *
     * @param \App\Models\Document $attachment
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Document $attachment)
    {
        try {
            // Check if user has permission to delete this attachment
            $user = Auth::user();
            
            // Allow deletion if user is admin, helpdesk, or the creator of the attachment
            if (!($user->hasAnyRole(['Administrator', 'Pentadbir Sistem', 'Penyelia Helpdesk', 'Helpdesk On Duty']) || 
                  $attachment->created_by == Auth::id())) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak mempunyai kebenaran untuk memadam lampiran ini.'
                ], 403);
            }

            // Check if it's a ticket attachment
            if ($attachment->doc_jenis !== 'TICKET') {
                return response()->json([
                    'success' => false,
                    'message' => 'Lampiran ini bukan lampiran tiket.'
                ], 400);
            }

            // Delete physical file
            $filePath = storage_path('app/public/ticket_attachments/' . $attachment->doc_storage_nm);
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            // Delete database record
            $attachment->delete();

            return response()->json([
                'success' => true,
                'message' => 'Lampiran berjaya dipadam.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ralat semasa memadam lampiran: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download ticket attachment file
     *
     * Downloads a specific attachment file by document ID.
     * Only allows downloads of documents with doc_jenis = 'TICKET'
     *
     * @param int $documentId
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function download($documentId)
    {
        $document = Document::where('id', $documentId)
            ->where('doc_jenis', 'TICKET')
            ->firstOrFail();

        $filePath = storage_path('app/public/ticket_attachments/' . $document->doc_storage_nm);

        if (!file_exists($filePath)) {
            abort(404, 'File tidak dijumpai');
        }

        return response()->download($filePath, $document->doc_name, [
            'Content-Type' => $document->doc_mime,
        ]);
    }

    /**
     * Handle file attachments for ticket (used internally by other controllers)
     *
     * Stores uploaded files and creates Document records with:
     * - Files saved to storage/app/public/ticket_attachments/
     * - doc_jenis = 'TICKET'
     * - doc_id = ticket ID
     *
     * @param array|\Illuminate\Http\UploadedFile $files
     * @param int $ticketId
     * @return void
     */
    public static function handleFileAttachments($files, $ticketId)
    {
        if (!is_array($files)) {
            $files = [$files];
        }

        foreach ($files as $file) {
            if ($file && $file->isValid()) {
                // Generate unique filename
                $originalName = $file->getClientOriginalName();
                $extension = $file->getClientOriginalExtension();
                $filename = time() . '_' . uniqid() . '.' . $extension;

                // Store file
                $path = $file->storeAs('ticket_attachments', $filename, 'public');

                // Create document record
                Document::create([
                    'doc_id' => $ticketId,
                    'uuid' => Str::uuid(),
                    'doc_mime' => $file->getMimeType(),
                    'doc_name' => $originalName,
                    'doc_storage_nm' => $filename,
                    'doc_jenis' => 'TICKET',
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id(),
                ]);
            }
        }
    }
}
