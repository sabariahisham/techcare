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
    background: #ffc107;
    color: #0a0a0a;
    padding: 14px;
    text-align: center;
    font-size: 16px;
    font-weight: 600;
    border-radius: 6px;
    margin-bottom: 18px;
">
    ⏳ SLA - Hampir Tamat Tempoh
</div>

Assalamualaikum dan Salam Sejahtera,

**Y.Bhg. Tuan/Puan,**

{{ $reminderMessage }}

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
        <td style="vertical-align: top; padding: 0 0 8px 0;"><strong>{{ $ticket->latestSlaDueDate ? \Carbon\Carbon::parse($ticket->latestSlaDueDate)->format('d/m/Y') : 'Tiada rekod' }}</strong></td>
    </tr>    
</table>
@endcomponent

Tiket ini akan tamat tempoh pada **{{ $ticket->latestSlaDueDate ? \Carbon\Carbon::parse($ticket->latestSlaDueDate)->format('d/m/Y') : 'Tiada rekod' }}**. Mohon tuan/puan mengambil tindakan segera untuk mengelakkan tiket daripada menjadi *<i>overdue</i>*.

@if($ticket->latestSlaDueDate)
@php
$dueDate = \Carbon\Carbon::parse($ticket->latestSlaDueDate);
$isMonday = $dueDate->isMonday();
@endphp
@if($isMonday)
<div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 12px; margin: 12px 0; font-size: 14px;">
📅 <strong>Nota:</strong> Tarikh akhir SLA jatuh pada hari Isnin. Email ini dihantar pada hari Jumaat (1 hari bekerja sebelum tempoh tamat) untuk makluman dan tindakan segera.
</div>
@endif
@endif

Mohon tindakan selanjutnya melalui pautan di bawah:

@component('mail::button', ['url' => route('tickets.show', $ticket->uuid), 'color' => 'primary'])
Lihat Tiket
@endcomponent

Terima kasih atas perhatian dan kerjasama tuan/puan.

**Pentadbir {{ config('app.name') }}**
@endcomponent
{{-- blade-formatter-enable --}}
