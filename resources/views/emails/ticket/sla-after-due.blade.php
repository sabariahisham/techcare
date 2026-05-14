{{-- blade-formatter-disable --}}
@component('mail::message')
{{-- Header --}}
@php
    $rawAssignedUsers = $ticket->chronologies()
        ->whereNotNull('escalate_to')
        ->with('escalatedTo')
        ->get()
        ->pluck('escalatedTo')
        ->filter()
        ->unique('id')
        ->values();

    $assignedOfficerNames = collect();

    if ($rawAssignedUsers->isNotEmpty()) {
        $userIds = $rawAssignedUsers->pluck('id')->all();
        $sortedUsers = \App\Models\User::whereIn('id', $userIds)
            ->get(['id', 'nama', 'nama_gred'])
            ->sort(function ($a, $b) {
                $gradeA = (int) $a->nama_gred;
                $gradeB = (int) $b->nama_gred;
                if ($gradeA !== $gradeB) {
                    return $gradeB - $gradeA;
                }
                return $a->id - $b->id;
            });
        $assignedOfficerNames = $sortedUsers->pluck('nama')->unique()->values();
    }
@endphp

<div style="
    background: #dc3545;
    color: #ffffff;
    padding: 14px;
    text-align: center;
    font-size: 16px;
    font-weight: 600;
    border-radius: 6px;
    margin-bottom: 18px;
">
   ⚠️ Tindakan Segera - SLA Tamat Tempoh (1 Hari Lepas)
</div>

Assalamualaikum dan Salam Sejahtera,

**Y.Bhg. Tuan/Puan,**

{{ $message }}

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
        <td style="vertical-align: top; padding: 0 0 8px 0;"><strong>{{ strtoupper($ticket->status->st_name) }}</strong></td>
    </tr>
    <tr>
        <td style="width: 140px; vertical-align: top; padding: 0 0 8px 0;"><strong>Pengadu</strong></td>
        <td style="width: 12px; vertical-align: top; padding: 0 0 8px 0;">:</td>
        <td style="vertical-align: top; padding: 0 0 8px 0;"><strong>{{ $ticket->user->nama }}</strong></td>
    </tr>
    <tr>
        <td colspan="3" style="padding: 10px 0 8px 0; border-bottom: 1px solid #dfe3e8;"></td>
    </tr>
    <tr>
        <td style="width: 140px; vertical-align: top; padding: 8px 0 8px 0;"><strong>Pegawai Tugasan</strong></td>
        <td style="width: 12px; vertical-align: top; padding: 8px 0 8px 0;">:</td>
        <td style="vertical-align: top; padding: 8px 0 8px 0;"><strong>{{ $assignedOfficerNames->isNotEmpty() ? $assignedOfficerNames->implode(', ') : 'Tiada rekod' }}</strong></td>
    </tr>    
    <tr>
        <td style="width: 140px; vertical-align: top; padding: 0 0 8px 0;"><strong>Tarikh Akhir SLA</strong></td>
        <td style="width: 12px; vertical-align: top; padding: 0 0 8px 0;">:</td>
        <td style="vertical-align: top; padding: 0 0 8px 0;"><strong>{{ $ticket->tick_duedate ? \Carbon\Carbon::parse($ticket->tick_duedate)->format('d/m/Y') : 'Tiada rekod' }}</strong></td>
    </tr>
</table>
@endcomponent

Mohon **tindakan segera** kerana isu ini telah melepasi **tempoh penyelesaian (SLA) yang telah ditetapkan**. Terima kasih atas perhatian tuan/puan.

Mohon tindakan selanjutnya melalui pautan di bawah:

@component('mail::button', ['url' => route('tickets.show', $ticket->uuid), 'color' => 'error'])
Lihat Tiket
@endcomponent

Terima kasih.

**Pentadbir {{ config('app.name') }}**
@endcomponent
{{-- blade-formatter-enable --}}
