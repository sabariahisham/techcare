<?php

namespace App\Console\Commands;

use App\Http\Controllers\Ticket\TicketDueDateController;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendTicketDueDateReminders extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'tickets:send-duedate-reminders';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Send email reminders to level 1 officers for tickets due tomorrow (status: Aduan Baru)';

  /**
   * Execute the console command.
   */
  public function handle()
  {
    $this->info('Starting ticket due date reminder process...');

    try {
      // Use controller to handle the business logic
      $controller = new TicketDueDateController();
      $result = $controller->sendDueDateReminders();

      $data = $result->getData();

      if ($data->success) {
        $this->info("\n✓ Process completed successfully");
        $this->info("Summary:");
        $this->info("  Total tickets: {$data->data->total}");
        $this->info("  Reminders sent: {$data->data->sent}");
        $this->info("  Failed: {$data->data->failed}");
        $this->info("  Skipped: {$data->data->skipped}");
        $this->info("  Date checked: {$data->data->date}");
        $this->info("  Looking for due date: {$data->data->checking_for_duedate}");

        return Command::SUCCESS;
      } else {
        $this->error("✗ Process failed: {$data->message}");
        return Command::FAILURE;
      }
    } catch (\Exception $e) {
      $this->error("Error in reminder process: {$e->getMessage()}");
      Log::error('Error in ticket due date reminder command', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
      ]);

      return Command::FAILURE;
    }
  }
}
