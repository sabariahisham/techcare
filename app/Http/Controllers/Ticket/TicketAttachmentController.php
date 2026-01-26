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
use App\Http\Requests\Ticket\StoreAttachmentRequest;
use App\Http\Requests\Ticket\DeleteAttachmentRequest;
use App\Http\Requests\Ticket\DownloadAttachmentRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class TicketAttachmentController extends Controller
{
  /**
   * Upload multiple files for a ticket
   *
   * @param \App\Http\Requests\Ticket\StoreAttachmentRequest $request
   * @return \Illuminate\Http\JsonResponse
   */
  public function upload(StoreAttachmentRequest $request)
  {
    try {
      // Get ticket to access ticket_nolog (ticket existence already validated in Request)
      $ticket = \App\Models\Ticket::find($request->ticket_id);

      $uploadedFiles = [];
      $files = $request->file('attachments');

      if (!is_array($files)) {
        $files = [$files];
      }

      // Get current attachment count for this ticket
      $currentCount = Document::where('doc_id', $request->ticket_id)
        ->where('doc_jenis', 'TICKET')
        ->count();

      $firstDocumentId = null;
      $fileIndex = 0;

      foreach ($files as $file) {
        if ($file && $file->isValid()) {
          $fileIndex++;
          $attachmentCount = $currentCount + $fileIndex;

          // Generate filename based on ticket_nolog + count
          $originalName = $file->getClientOriginalName();
          $extension = $file->getClientOriginalExtension();
          $storageFileName = $ticket->ticket_nolog . '-' . $attachmentCount . '.' . $extension;

          // Store file with the new naming convention
          $path = $file->storeAs('ticket_attachments', $storageFileName, 'public');

          Log::info('File saved to storage', [
            'path' => $path,
            'storage_filename' => $storageFileName,
            'ticket_id' => $request->ticket_id
          ]);

          // Create document record
          try {
            $document = Document::create([
              'doc_id' => $request->ticket_id,
              'uuid' => Str::uuid(),
              'doc_mime' => $file->getMimeType(),
              'doc_name' => $originalName,
              'doc_storage_nm' => $storageFileName,
              'doc_jenis' => 'TICKET',
              'created_by' => Auth::id(),
              'updated_by' => Auth::id(),
            ]);

            Log::info('Document record created', [
              'document_id' => $document->id,
              'doc_name' => $originalName,
              'doc_storage_nm' => $storageFileName
            ]);

            // Store the first document ID to update ticket
            if ($firstDocumentId === null) {
              $firstDocumentId = $document->id;
            }

            $uploadedFiles[] = [
              'id' => $document->id,
              'name' => $originalName,
              'storage_name' => $storageFileName,
              'size' => $file->getSize(),
              'type' => $file->getMimeType()
            ];
          } catch (\Exception $docException) {
            Log::error('Failed to create document record', [
              'ticket_id' => $request->ticket_id,
              'storage_filename' => $storageFileName,
              'error' => $docException->getMessage(),
              'trace' => $docException->getTraceAsString()
            ]);

            // Delete the file from storage if database insert fails
            Storage::delete('public/ticket_attachments/' . $storageFileName);

            throw $docException; // Re-throw to be caught by outer try-catch
          }
        }
      }

      // Update ticket.doc_id to reference the first document if we created any and ticket doesn't have doc_id
      if ($firstDocumentId && $ticket->doc_id === null) {
        $ticket->update(['doc_id' => $firstDocumentId]);
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
   * @param \App\Http\Requests\Ticket\DeleteAttachmentRequest $request
   * @param string $uuid
   * @return \Illuminate\Http\JsonResponse
   */
  public function destroy(DeleteAttachmentRequest $request, $uuid)
  {
    // Find document by UUID
    $document = Document::where('uuid', $uuid)
      ->where('doc_jenis', 'TICKET')
      ->firstOrFail();

    try {
      Log::info('Attempting to delete attachment', [
        'attachment_id' => $document->id,
        'doc_name' => $document->doc_name,
        'doc_storage_nm' => $document->doc_storage_nm,
        'doc_jenis' => $document->doc_jenis,
        'user_id' => Auth::id()
      ]);

      // Authorization and document type validation handled by DeleteAttachmentRequest

      // Store attachment info before deletion
      $attachmentId = $document->id;
      $storageFileName = $document->doc_storage_nm;
      $docName = $document->doc_name;

      // Check and delete physical file first
      $fileDeleted = false;
      if ($storageFileName) {
        $filePath = 'public/ticket_attachments/' . $storageFileName;

        Log::info('Checking file existence', [
          'file_path' => $filePath,
          'exists' => Storage::exists($filePath)
        ]);

        if (Storage::exists($filePath)) {
          $fileDeleted = Storage::delete($filePath);
          Log::info('File deletion attempt', [
            'file_path' => $filePath,
            'deleted' => $fileDeleted
          ]);

          if (!$fileDeleted) {
            Log::error('Failed to delete physical file', [
              'attachment_id' => $attachmentId,
              'storage_filename' => $storageFileName,
              'file_path' => $filePath
            ]);
            return response()->json([
              'success' => false,
              'message' => 'Gagal memadam fail fizikal.'
            ], 500);
          }
        } else {
          Log::info('Physical file does not exist, skipping file deletion', [
            'file_path' => $filePath
          ]);
          $fileDeleted = true; // Consider it deleted if it doesn't exist
        }
      } else {
        Log::warning('No storage filename found', [
          'attachment_id' => $attachmentId
        ]);
        $fileDeleted = true; // No file to delete
      }

      // Force delete database record (permanently delete instead of soft delete)
      Log::info('Attempting to force delete database record', [
        'attachment_id' => $attachmentId
      ]);

      $deleted = $document->forceDelete();

      Log::info('Database deletion result', [
        'attachment_id' => $attachmentId,
        'deleted' => $deleted
      ]);

      if (!$deleted) {
        Log::error('Failed to delete attachment record from database', [
          'attachment_id' => $attachmentId
        ]);
        return response()->json([
          'success' => false,
          'message' => 'Gagal memadam rekod lampiran daripada pangkalan data.'
        ], 500);
      }

      // Verify deletion by trying to find the record
      $verifyDeleted = Document::withTrashed()->find($attachmentId);
      if ($verifyDeleted && $verifyDeleted->trashed()) {
        Log::warning('Record was soft deleted instead of force deleted', [
          'attachment_id' => $attachmentId
        ]);
        // Try force delete again
        $verifyDeleted->forceDelete();
      }

      Log::info('Attachment successfully deleted', [
        'attachment_id' => $attachmentId,
        'storage_filename' => $storageFileName,
        'doc_name' => $docName,
        'file_deleted' => $fileDeleted,
        'db_deleted' => $deleted,
        'user_id' => Auth::id()
      ]);

      return response()->json([
        'success' => true,
        'message' => 'Lampiran berjaya dipadam.'
      ]);
    } catch (\Exception $e) {
      Log::error('Exception during attachment deletion', [
        'attachment_id' => $attachment->id ?? 'unknown',
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
      ]);

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
  /**
   * Download an attachment
   *
   * @param \App\Http\Requests\Ticket\DownloadAttachmentRequest $request
   * @param string $uuid
   * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
   */
  public function download(DownloadAttachmentRequest $request, $uuid)
  {
    // Find document by UUID
    $document = Document::where('uuid', $uuid)
      ->where('doc_jenis', 'TICKET')
      ->firstOrFail();

    $storagePath = 'public/ticket_attachments/' . $document->doc_storage_nm;

    if (!Storage::exists($storagePath)) {
      abort(404, 'Fail tidak dijumpai');
    }

    // Get the full path from storage
    $filePath = Storage::path($storagePath);

    // Check if user wants to view inline (in browser) or download
    $isView = $request->query('view') === '1' || $request->query('view') === 1;

    if ($isView) {
      // Return file for inline viewing in browser
      return response()->file($filePath, [
        'Content-Type' => $document->doc_mime,
        'Content-Disposition' => 'inline; filename="' . $document->doc_name . '"'
      ]);
    } else {
      // Return file as download
      return response()->download($filePath, $document->doc_name, [
        'Content-Type' => $document->doc_mime,
      ]);
    }
  }

  /**
   * Handle file attachments for ticket (used internally by other controllers)
   *
   * Stores uploaded files and creates Document records with:
   * - Files saved to storage/app/public/ticket_attachments/
   * - doc_jenis = 'TICKET'
   * - doc_id = ticket ID
   * - doc_storage_nm follows pattern: ticket_nolog-X (where X is file count)
   * - Updates ticket.doc_id to reference the first document's ID
   *
   * @param array|\Illuminate\Http\UploadedFile $files
   * @param int $ticketId
   * @return void
   */
  public static function handleFileAttachments($files, $ticketId)
  {
    // Get ticket to access ticket_nolog
    $ticket = \App\Models\Ticket::find($ticketId);
    if (!$ticket) {
      return;
    }

    if (!is_array($files)) {
      $files = [$files];
    }

    // Get current attachment count for this ticket
    $currentCount = Document::where('doc_id', $ticketId)
      ->where('doc_jenis', 'TICKET')
      ->count();

    $firstDocumentId = null;
    $fileIndex = 0;

    foreach ($files as $file) {
      if ($file && $file->isValid()) {
        $fileIndex++;
        $attachmentCount = $currentCount + $fileIndex;

        // Generate filename based on ticket_nolog + count
        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $storageFileName = $ticket->ticket_nolog . '-' . $attachmentCount . '.' . $extension;

        // Store file with the new naming convention
        $path = $file->storeAs('ticket_attachments', $storageFileName, 'public');

        Log::info('Attachment file stored', [
          'ticket_id' => $ticketId,
          'storage_path' => $path,
          'storage_filename' => $storageFileName,
          'original_name' => $originalName
        ]);

        // Create document record
        try {
          $document = Document::create([
            'doc_id' => $ticketId,
            'uuid' => Str::uuid(),
            'doc_mime' => $file->getMimeType(),
            'doc_name' => $originalName,
            'doc_storage_nm' => $storageFileName,
            'doc_jenis' => 'TICKET',
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
          ]);

          Log::info('Attachment document record created', [
            'document_id' => $document->id,
            'ticket_id' => $ticketId,
            'doc_storage_nm' => $storageFileName
          ]);

          // Store the first document ID to update ticket
          if ($firstDocumentId === null) {
            $firstDocumentId = $document->id;
          }
        } catch (\Exception $e) {
          Log::error('Failed to create attachment document record', [
            'ticket_id' => $ticketId,
            'storage_filename' => $storageFileName,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
          ]);

          // Don't throw exception, just log it and continue with other files
          // The file is already stored, so the issue is with database only
        }
      }
    }

    // Update ticket.doc_id to reference the first document if we created any
    if ($firstDocumentId && $ticket->doc_id === null) {
      $ticket->update(['doc_id' => $firstDocumentId]);
    }
  }
}
