{{-- blade-formatter-disable --}}
@component('mail::message')
{{-- Header --}}
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
    ❕Tindakan Segera - SLA Tamat Tempoh (1 Hari Lepas)
</div>

Assalamualaikum dan Salam Sejahtera,

**Tuan/Puan,**

{{ $message }}

@component('mail::panel')
## **Maklumat Tiket**

**No. Log** : <strong>{{ $ticket->ticket_nolog }}</strong>

**Tajuk** : <strong>{{ $ticket->ticket_title }}</strong>

**Status** : <strong>{{ $ticket->status->st_name ?? 'N/A' }}</strong>

**Pengadu** : <strong>{{ $ticket->user->nama ?? 'N/A' }}</strong>

**Pegawai Tugasan** : <strong>{{ $ticket->assignedUser->name ?? 'Belum Ditugaskan' }}</strong> 

**Tarikh Akhir SLA** : <strong>{{ $ticket->tick_duedate? \Carbon\Carbon::parse($ticket->tick_duedate)->format('d/m/Y') : 'Tiada' }}</strong>
@endcomponent

Mohon **tindakan segera** kerana isu ini telah melepasi **tempoh penyelesaian (SLA) yang telah ditetapkan**. Terima kasih atas perhatian tuan/puan.

Sila akses tiket melalui pautan berikut:

@component('mail::button', ['url' => route('tickets.show', $ticket->uuid), 'color' => 'error'])
Lihat Tiket
@endcomponent

Terima kasih.

**Pentadbir {{ config('app.name') }}**
@endcomponent
{{-- blade-formatter-enable --}}
