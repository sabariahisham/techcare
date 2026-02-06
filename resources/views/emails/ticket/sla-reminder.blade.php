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
    ⏳ Peringatan SLA - Hampir Tamat Tempoh
</div>

Assalamualaikum dan Salam Sejahtera,

**Tuan/Puan,**

{{ $reminderMessage }}

@component('mail::panel')
## **Maklumat Tiket**

**No. Log** : <strong>{{ $ticket->ticket_nolog }}</strong>

**Tajuk** : <strong>{{ $ticket->ticket_title }}</strong>

**Status** : <strong>{{ $ticket->status->st_name ?? 'N/A' }}</strong>

**Pengadu** : <strong>{{ $ticket->user->nama ?? 'N/A' }}</strong>

**Pegawai Tugasan** : <strong>{{ $ticket->assignedUser->name ?? 'Belum Ditugaskan' }}</strong> 

**Tarikh Akhir SLA** : <strong>{{ $ticket->latestSlaDueDate ? \Carbon\Carbon::parse($ticket->latestSlaDueDate)->format('d/m/Y H:i') : 'Tiada' }}</strong>
@endcomponent

Tiket ini akan tamat tempoh **esok**. Mohon tuan/puan mengambil tindakan segera untuk mengelakkan tiket daripada menjadi overdue.

Sila akses tiket melalui pautan berikut:

@component('mail::button', ['url' => route('tickets.show', $ticket->uuid), 'color' => 'primary'])
Lihat Tiket
@endcomponent

Terima kasih atas perhatian dan kerjasama tuan/puan.

**Pentadbir {{ config('app.name') }}**
@endcomponent
{{-- blade-formatter-enable --}}
