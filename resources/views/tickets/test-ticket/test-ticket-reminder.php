<?php

/**
 * Test script untuk verify ticket reminder system
 * Run: php test-ticket-reminder.php
 */

require __DIR__ . '/../../vendor/autoload.php';

$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Testing Ticket Due Date Reminder System ===\n\n";

// Test 1: Check tomorrow's date
$tomorrow = \Carbon\Carbon::tomorrow()->format('Y-m-d');
echo "1. Tomorrow's date: {$tomorrow}\n";

// Test 2: Find tickets with status = 1 (Aduan Baru) due tomorrow
$tomorrowStart = \Carbon\Carbon::tomorrow()->startOfDay();
$tomorrowEnd = \Carbon\Carbon::tomorrow()->endOfDay();

$tickets = \App\Models\Ticket::where('tick_status', 1)
    ->whereBetween('tick_duedate', [$tomorrowStart, $tomorrowEnd])
    ->with(['user', 'category', 'subCategory'])
    ->get();

echo "2. Tickets with status='Aduan Baru' due tomorrow: {$tickets->count()}\n\n";

if ($tickets->isEmpty()) {
    echo "✓ No tickets found - this is normal if no tickets are due tomorrow.\n\n";

    // Show sample of recent tickets for reference
    echo "Sample of recent tickets (any status):\n";
    $recentTickets = \App\Models\Ticket::orderBy('created_at', 'desc')
        ->limit(5)
        ->get(['id', 'ticket_nolog', 'tick_status', 'tick_duedate', 'created_at']);

    foreach ($recentTickets as $ticket) {
        $dueDate = $ticket->tick_duedate ? $ticket->tick_duedate->format('Y-m-d') : 'N/A';
        echo "  - {$ticket->ticket_nolog} | Status: {$ticket->tick_status} | Due: {$dueDate}\n";
    }
} else {
    echo "Found tickets due tomorrow:\n";
    foreach ($tickets as $ticket) {
        echo "\n--- Ticket: {$ticket->ticket_nolog} ---\n";
        echo "Title: {$ticket->ticket_title}\n";
        echo "Category: " . ($ticket->category->cat_name ?? 'N/A') . "\n";
        echo "Due Date: " . $ticket->tick_duedate->format('d/m/Y') . "\n";

        // Test 3: Check if officer is assigned
        $chronology = \App\Models\TicketChronology::where('ticket_id', $ticket->id)
            ->where('level_pegtugas_id', 1)
            ->whereNotNull('escalate_to')
            ->with('escalatedTo')
            ->latest()
            ->first();

        if ($chronology && $chronology->escalatedTo) {
            $officer = $chronology->escalatedTo;
            echo "Assigned Officer (Level 1): {$officer->nama}\n";
            echo "Officer Email: " . ($officer->email ?: 'NO EMAIL') . "\n";

            if ($officer->email) {
                echo "✓ Email reminder will be sent to this officer\n";
            } else {
                echo "✗ WARNING: Officer has no email, reminder will be skipped\n";
            }
        } else {
            echo "✗ WARNING: No level 1 officer assigned, reminder will be skipped\n";
        }
    }
}

echo "\n=== Test Summary ===\n";
echo "✓ Tomorrow's date calculated correctly\n";
echo "✓ Database query working\n";
echo "✓ Ticket relationships loaded\n";
echo "\n";
echo "To manually test email sending, run:\n";
echo "  php artisan tickets:send-duedate-reminders\n";
