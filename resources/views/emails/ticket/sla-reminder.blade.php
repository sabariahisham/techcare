{{-- blade-formatter-disable --}}
@component('mail::message')
{{-- Header --}}

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

**Tuan/Puan,**

{{ $reminderMessage }}

@component('mail::panel')
## **Maklumat Tiket**

**No. Log** : <strong>{{ $ticket->ticket_nolog }}</strong><br>
**Tajuk** : <strong>{{ $ticket->ticket_title }}</strong><br>
**Status** : <strong>{{ $ticket->status->st_name }}</strong><br>
**Pengadu** : <strong>{{ $ticket->user->nama }}</strong><br>
**Pegawai Tugasan** : <strong>{{ $ticket->assignedUser->nama ?? 'Tiada rekod' }}</strong><br>
**Tarikh Akhir SLA** : <strong>{{ $ticket->latestSlaDueDate ? \Carbon\Carbon::parse($ticket->latestSlaDueDate)->format('d/m/Y') : 'Tiada rekod' }}</strong>
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

Sila akses tiket melalui pautan berikut:

@component('mail::button', ['url' => route('tickets.show', $ticket->uuid), 'color' => 'primary'])
Lihat Tiket
@endcomponent

Terima kasih atas perhatian dan kerjasama tuan/puan.

**Pentadbir {{ config('app.name') }}**
@endcomponent
{{-- blade-formatter-enable --}}
