<?php

/*
 * Function TicketEmailController.php:
 *  - Manage email notifications related to tickets for Pengadu and Pegawai Tugasan.
 *  - Configure email settings and templates.
 *  - Send alerts for ticket status: Baru(1), Selesai(6) and Dirujuk(4).
 *  - Provide email templates for Pengadu and Pegawai Tugasan.
 */

namespace App\Http\Controllers\Ticket;

use App\Models\Ticket;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;

class TicketEmailController extends Controller
{
    /**
     * Send notification email for ticket
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Ticket $ticket
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendNotification(Request $request, Ticket $ticket)
    {
        $request->validate([
            'notification_type' => 'required|string|in:created,updated,assigned,resolved',
            'recipients' => 'array',
            'recipients.*' => 'email'
        ]);

        try {
            // Load ticket with relationships
            $ticket->load(['user', 'status', 'category', 'subCategory']);

            $notificationType = $request->notification_type;
            $recipients = $request->recipients ?? [];

            // Determine default recipients if not provided
            if (empty($recipients)) {
                $recipients = $this->getDefaultRecipients($ticket, $notificationType);
            }

            // Send notifications based on type
            switch ($notificationType) {
                case 'created':
                    $this->sendTicketCreatedNotification($ticket, $recipients);
                    break;
                case 'updated':
                    $this->sendTicketUpdatedNotification($ticket, $recipients);
                    break;
                case 'assigned':
                    $this->sendTicketAssignedNotification($ticket, $recipients);
                    break;
                case 'resolved':
                    $this->sendTicketResolvedNotification($ticket, $recipients);
                    break;
            }

            return response()->json([
                'success' => true,
                'message' => 'Notifikasi email berjaya dihantar.',
                'recipients_count' => count($recipients)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ralat semasa menghantar notifikasi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get default recipients based on ticket and notification type
     */
    private function getDefaultRecipients($ticket, $notificationType)
    {
        $recipients = [];

        // Always include ticket creator/complainant
        if ($ticket->user && $ticket->user->email) {
            $recipients[] = $ticket->user->email;
        }

        // Include assigned officers for certain notification types
        if (in_array($notificationType, ['assigned', 'updated'])) {
            $latestAssignment = $ticket->chronologies()
                ->whereNotNull('escalate_to')
                ->with('escalatedTo')
                ->latest()
                ->first();

            if ($latestAssignment && $latestAssignment->escalatedTo && $latestAssignment->escalatedTo->email) {
                $recipients[] = $latestAssignment->escalatedTo->email;
            }
        }

        return array_unique($recipients);
    }

    /**
     * Send ticket created notification
     */
    private function sendTicketCreatedNotification($ticket, $recipients)
    {
        // TODO: Implement email template and sending logic
        // This is a placeholder for now
    }

    /**
     * Send ticket updated notification
     */
    private function sendTicketUpdatedNotification($ticket, $recipients)
    {
        // TODO: Implement email template and sending logic
        // This is a placeholder for now
    }

    /**
     * Send ticket assigned notification
     */
    private function sendTicketAssignedNotification($ticket, $recipients)
    {
        // TODO: Implement email template and sending logic
        // This is a placeholder for now
    }

    /**
     * Send ticket resolved notification
     */
    private function sendTicketResolvedNotification($ticket, $recipients)
    {
        // TODO: Implement email template and sending logic
        // This is a placeholder for now
    }
}
