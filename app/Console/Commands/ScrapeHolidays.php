<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\PublicHoliday;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use voku\helper\HtmlDomParser;

class ScrapeHolidays extends Command
{
    protected $signature = 'scrape:holidays {year?}';

    protected $description = 'Scrape public holidays data from publicholidays.com.my';

    public function handle(): int
    {
        $year = (int) ($this->argument('year') ?? date('Y'));
        $url = "https://publicholidays.com.my/{$year}-dates/";

        $this->info("Memulakan proses scraping dari: {$url}");

        $response = Http::withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'Accept' => 'text/html,application/xhtml+xml',
        ])->get($url);

        if ($response->failed()) {
            $this->error("Gagal mengakses laman web. Status: {$response->status()}");

            return Command::FAILURE;
        }

        $dom = HtmlDomParser::str_get_html($response->body());

        // The page renders three tables sharing the .publicholidays class: the
        // requested year followed by the next two years. Only the first table
        // belongs to $year, so scrape just that one. Otherwise the later years'
        // rows get force-stamped with $year below and pollute the data with
        // impossible duplicate dates (e.g. Chinese New Year on three dates).
        $table = $dom->find('table.publicholidays', 0) ?: $dom->find('table', 0);
        $rows = $table ? $table->find('tr') : [];

        $count = 0;

        foreach ($rows as $row) {
            // Get cells - skip if no valid date cell
            $dateCell = $row->find('td', 0);
            if (! $dateCell) {
                continue;
            }

            $dateTextRaw = $dateCell->text();
            $dateText = is_array($dateTextRaw) ? implode(' ', $dateTextRaw) : (string) $dateTextRaw;
            $holidayCell = $row->find('td', 2);
            $stateTextRaw = $row->find('td', 3)?->text();
            $stateText = is_array($stateTextRaw) ? implode(' ', $stateTextRaw) : (string) ($stateTextRaw ?? '');

            if (! $dateText || ! $holidayCell) {
                continue;
            }

            // Extract date: "1 Jan" -> "1 Jan"
            $cleanedDate = trim(preg_replace('/\s+/', ' ', $dateText));
            // Remove day of week if present (e.g., "1 Jan Thu" -> "1 Jan")
            $dateParts = explode(' ', $cleanedDate);
            if (count($dateParts) >= 2) {
                $cleanedDate = $dateParts[0].' '.$dateParts[1];
            }

            // Extract holiday name from link or direct text
            $holidayLink = $holidayCell->find('a.summary', 0);
            $holidayText = $holidayLink ? $holidayLink->text() : $holidayCell->text();
            $holidayName = is_array($holidayText) ? implode(' ', $holidayText) : (string) $holidayText;
            $holidayName = trim($holidayName);

            if (! $cleanedDate || ! $holidayName) {
                continue;
            }

            try {
                $carbonDate = Carbon::createFromFormat('d M Y', $cleanedDate.' '.$year);
                $formattedDate = $carbonDate->format('Y-m-d');
                $dayName = $carbonDate->format('l'); // Get day name (Monday, Tuesday, etc)
            } catch (\Exception $e) {
                $this->warn("Gagal parse tarikh: {$cleanedDate} - {$e->getMessage()}");

                continue;
            }

            // Parse states - handle "National except" pattern or multiple states
            $result = $this->parseStateInfo($stateText);

            // Create single record per holiday
            // Note: Eloquent will automatically JSON encode when saving, so pass arrays directly
            PublicHoliday::updateOrCreate(
                ['holiday_date' => $formattedDate, 'holiday_name' => $holidayName],
                [
                    'day' => $dayName,
                    'states' => $result['states'],
                    'states_except' => $result['states_except'],
                    'updated_at' => now(),
                ]
            );

            $count++;
        }

        $this->info("Scraping selesai! {$count} rekod bagi tahun {$year} dikemaskini.");

        return Command::SUCCESS;
    }

    /**
     * Parse state information from the state text
     * Returns array with states and states_except as arrays
     */
    private function parseStateInfo(string $stateText): array
    {
        if (empty($stateText)) {
            return ['states' => null, 'states_except' => null];
        }

        $stateText = trim($stateText);

        // Check if it contains "National"
        if (str_contains(strtolower($stateText), 'national')) {
            // Extract "except" states if present
            if (str_contains(strtolower($stateText), 'except')) {
                // Extract text after "except"
                $exceptText = preg_replace('/^.*?except\s*/i', '', $stateText);
                $exceptText = preg_replace('/\s+/', ' ', $exceptText);
                $exceptText = str_replace('&', 'and', $exceptText);

                // Parse state names
                $exceptStates = $this->parseExceptStates($exceptText);

                // Still nationwide (minus the exceptions), so mark states as Nasional.
                return [
                    'states' => ['Nasional'],
                    'states_except' => $exceptStates ?: null,
                ];
            }

            // Just "National" without except
            return ['states' => ['Nasional'], 'states_except' => null];
        }

        // Check if multiple states are listed (separated by comma or &)
        if (str_contains($stateText, ',') || str_contains($stateText, '&')) {
            $states = [];
            $parts = preg_split('/[,&]/', $stateText);

            foreach ($parts as $part) {
                $part = trim($part);
                if (empty($part)) {
                    continue;
                }

                $mappedName = $this->mapStatePartToName($part);
                if ($mappedName) {
                    $states[] = $mappedName;
                } else {
                    $this->warn("Unmapped state in multi-state: {$part}");
                }
            }

            if (count($states) > 0) {
                return ['states' => $states, 'states_except' => null];
            }
        }

        // Single state - find the state name
        $stateName = $this->mapStatePartToName($stateText);

        return ['states' => $stateName ? [$stateName] : null, 'states_except' => null];
    }

    /**
     * Parse except states text and return array of state names
     */
    private function parseExceptStates(string $exceptText): array
    {
        $states = [];
        $parts = array_map('trim', preg_split('/[,&]/', $exceptText));

        foreach ($parts as $part) {
            $part = trim($part);
            if (empty($part)) {
                continue;
            }

            // Find matching state
            $stateName = $this->mapStatePartToName($part);
            if ($stateName) {
                $states[] = $stateName;
            } else {
                $this->warn("Unmapped except state: {$part}");
            }
        }

        return $states;
    }

    /**
     * Map a state part/abbreviation to full state name
     */
    private function mapStatePartToName(string $part): ?string
    {
        $mapping = [
            'Johor' => 'Johor',
            'Kedah' => 'Kedah',
            'Kelantan' => 'Kelantan',
            'Melaka' => 'Melaka',
            'Negeri Sembilan' => 'Negeri Sembilan',
            'NegeriSembilan' => 'Negeri Sembilan',
            'Pahang' => 'Pahang',
            'Pulau Pinang' => 'Pulau Pinang',
            'Penang' => 'Pulau Pinang',
            'Perak' => 'Perak',
            'Perlis' => 'Perlis',
            'Selangor' => 'Selangor',
            'Terengganu' => 'Terengganu',
            'Sabah' => 'Sabah',
            'Sarawak' => 'Sarawak',
            'Kuala Lumpur' => 'Wilayah Persekutuan Kuala Lumpur',
            'KualaLumpur' => 'Wilayah Persekutuan Kuala Lumpur',
            'KL' => 'Wilayah Persekutuan Kuala Lumpur',
            'Labuan' => 'Wilayah Persekutuan Labuan',
            'Putrajaya' => 'Wilayah Persekutuan Putrajaya',
        ];

        if (isset($mapping[$part])) {
            return $mapping[$part];
        }

        // Try partial match
        foreach ($mapping as $key => $value) {
            if (str_contains(strtolower($part), strtolower($key)) ||
                str_contains(strtolower($key), strtolower($part))) {
                return $value;
            }
        }

        return null;
    }
}
