{{-- blade-formatter-disable --}}
@component('mail::message')
{{-- Dynamic header based on status --}}
{{-- @php
    //$isClosed = (int) $ticket->tick_status === 6;
    $isClosed = (int) ($ticket->tick_status ?? $ticket->status_id) === 6;
@endphp --}}
@php
    $statusId = (int) ($newStatus ?? 0);
    $isClosed = in_array($statusId, [5, 6], true);
    //$resolvedByName = filled($changedBy->nama ?? null) ? $changedBy->nama : 'Tiada rekod';

    $headerStyle = match ($statusId) {
        1 => ['bg' => '#696cff', 'text' => '📌 Tiket Baru'],
        4 => ['bg' => '#696cff', 'text' => '📌 Tiket Dirujuk'],
        5 => ['bg' => '#6c757d', 'text' => '🚫 Tiket Dibatalkan'],
        6 => ['bg' => '#2e7d32', 'text' => '📂 Tiket Ditutup'],
        default => ['bg' => '#696cff', 'text' => '🔔 Status Aduan'],
    };

    $statusMessage = match ($statusId) {
        1 => 'Tiket aduan telah berjaya direkodkan dan akan diproses.',
        4 => 'Tiket aduan ini telah **dirujuk kepada pegawai bertanggungjawab** untuk tindakan lanjut.',
        5 => 'Tiket aduan ini telah **dibatalkan**. Sila ambil maklum untuk rekod tuan/puan.',
        6 => 'Tiket aduan ini telah **selesai diproses dan ditutup**. Terima kasih atas **kerjasama tuan/puan**.',
        default => 'Status tiket aduan telah dikemaskini. Sila semak butiran tiket untuk makluman lanjut.',
    };

    $rawAssignedUsers = $assignedUsers ?? $assignedUser ?? [];

    if ($rawAssignedUsers instanceof \Illuminate\Support\Collection) {
        $rawAssignedUsers = $rawAssignedUsers->all();
    }

    if (is_string($rawAssignedUsers)) {
        $decodedAssignedUsers = json_decode($rawAssignedUsers, true);
        $rawAssignedUsers = json_last_error() === JSON_ERROR_NONE && is_array($decodedAssignedUsers)
            ? $decodedAssignedUsers
            : preg_split('/\s*,\s*/', $rawAssignedUsers, -1, PREG_SPLIT_NO_EMPTY);
    }

    if (! is_array($rawAssignedUsers)) {
        $rawAssignedUsers = [$rawAssignedUsers];
    }

    $assignedOfficerNames = collect($rawAssignedUsers)
        ->flatMap(function ($item) {
            if (blank($item)) {
                return [];
            }

            if (is_object($item)) {
                return filled($item->nama ?? null) ? [$item->nama] : [];
            }

            if (is_array($item)) {
                if (filled($item['nama'] ?? null)) {
                    return [$item['nama']];
                }

                return collect($item)
                    ->filter(fn ($nested) => filled($nested))
                    ->map(fn ($nested) => is_scalar($nested) ? trim((string) $nested) : null)
                    ->filter()
                    ->values()
                    ->all();
            }

            return [trim((string) $item)];
        })
        ->filter()
        ->unique()
        ->values();

    if ($assignedOfficerNames->isNotEmpty()) {
        $sortedUsers = \App\Models\User::whereIn('nama', $assignedOfficerNames->all())
            ->get(['id', 'nama', 'nama_gred'])
            ->sort(function ($a, $b) {
                $gradeA = (int) $a->nama_gred;
                $gradeB = (int) $b->nama_gred;
                if ($gradeA !== $gradeB) {
                    return $gradeB - $gradeA;
                }
                return $a->id - $b->id;
            });
        if ($sortedUsers->isNotEmpty()) {
            $assignedOfficerNames = $sortedUsers->pluck('nama')->unique()->values();
        }
    }

    $finalActionLabel = match ($statusId) {
        5 => 'Dibatalkan Oleh',
        6 => 'Diselesaikan Oleh',
        default => null,
    };
@endphp

{{-- @component('mail::panel') --}}
{{-- Aduan Selesai(6) background Hijau & text berubah --}}
<div style="
    background: {{ $headerStyle['bg'] }};
    color: #ffffff;
    padding: 14px;
    text-align: center;
    font-size: 16px;
    font-weight: 600;
    border-radius: 6px;
    margin-bottom: 18px;
">
    {{ $headerStyle['text'] }}
</div>
{{-- @endcomponent --}}

Assalamualaikum dan Salam Sejahtera, 

**Y.Bhg.Tan Sri/Puan Sri/Datuk/Dato/Datin/Tuan/Puan,**

{!! $statusMessage !!}

@component('mail::panel')
## **Maklumat Tiket**

<table role="presentation" width="100%" style="border-collapse: collapse;">
    <tr>
        <td style="width: 140px; vertical-align: top; padding: 0 0 8px 0;"><strong>No. Log</strong></td>
        <td style="width: 12px; vertical-align: top; padding: 0 0 8px 0;">:</td>
        <td style="vertical-align: top; padding: 0 0 8px 0;"><strong>{{ $ticket->ticket_nolog }}</strong></td>
    </tr>
    <tr>
        <td style="width: 140px; vertical-align: top; padding: 0 0 8px 0;"><strong>Tajuk</strong></td>
        <td style="width: 12px; vertical-align: top; padding: 0 0 8px 0;">:</td>
        <td style="vertical-align: top; padding: 0 0 8px 0;"><strong>{{ strtoupper($ticket->ticket_title) }}</strong></td>
    </tr>
    <tr>
        <td style="width: 140px; vertical-align: top; padding: 0 0 8px 0;"><strong>Status</strong></td>
        <td style="width: 12px; vertical-align: top; padding: 0 0 8px 0;">:</td>
        <td style="vertical-align: top; padding: 0 0 8px 0;"><strong>{{ strtoupper($statusName ?? ($ticket->status->st_name)) }}</strong></td>
    </tr>
    <tr>
        <td style="width: 140px; vertical-align: top; padding: 0;"><strong>Pegawai Tugasan</strong></td>
        <td style="width: 12px; vertical-align: top; padding: 0;">:</td>
        <td style="vertical-align: top; padding: 0;"><strong>{{ $assignedOfficerNames->isNotEmpty() ? $assignedOfficerNames->implode(', ') : 'Tiada rekod' }}</strong></td>
    </tr>
    @if($isClosed && $finalActionLabel)
    <tr>
        <td style="width: 140px; vertical-align: top; padding: 0;"><strong>{{ $finalActionLabel }}</strong></td>
        <td style="width: 12px; vertical-align: top; padding: 0;">:</td>
        <td style="vertical-align: top; padding: 0;"><strong>{{ $changedBy->nama ?? 'Tiada rekod' }}</strong></td>
    </tr>
    @endif
</table>
@endcomponent

Sila semak status tiket melalui pautan berikut:

@component('mail::button', ['url' => route('tickets.show', $ticket->uuid)])
Lihat Tiket
@endcomponent

Terima kasih.

**Pentadbir {{ config('app.name') }}**
@endcomponent
{{-- blade-formatter-enable --}}
