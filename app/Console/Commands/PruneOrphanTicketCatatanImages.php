<?php

/*
  Sweeps ticket_catatan_images rows that were uploaded from the Quill editor
  (catatan tindakan / komen) but never got attached to a saved comment — e.g.
  the user inserted an image then removed it again before submitting, or
  closed the modal/navigated away without submitting at all. Deleting each
  model also removes its physical file via TicketCatatanImage's `deleting` hook.
*/

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TicketCatatanImage;
use Carbon\Carbon;

class PruneOrphanTicketCatatanImages extends Command
{
  protected $signature = 'ticket-catatan-images:prune-orphans {--hours=24 : Age threshold before an unattached image is considered orphaned}';
  protected $description = 'Delete ticket catatan tindakan images that were uploaded but never attached to a saved comment';

  public function handle()
  {
    $hours = (int) $this->option('hours');
    $threshold = Carbon::now()->subHours($hours);

    $orphans = TicketCatatanImage::whereNull('comment_id')
      ->where('created_at', '<', $threshold)
      ->get();

    if ($orphans->isEmpty()) {
      $this->info('No orphan catatan tindakan images found.');
      return self::SUCCESS;
    }

    $orphans->each->delete();

    $this->info("Deleted {$orphans->count()} orphan catatan tindakan image(s) older than {$hours} hour(s).");

    return self::SUCCESS;
  }
}
