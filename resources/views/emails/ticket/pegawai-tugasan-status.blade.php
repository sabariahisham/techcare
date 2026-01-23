{{-- blade-formatter-disable --}}
@component('mail::message')
{{-- Dynamic header based on status --}}
@php
    $isClosed = (int) $ticket->tick_status === 6;
@endphp

@component('mail::panel')
{{-- Aduan Selesai(6) background Hijau & text berubah --}}
<div style="
    background: {{ $isClosed ? '#2e7d32' : '#ffab00' }};
    color: #ffffff;
    padding: 14px;
    text-align: center;
    font-size: 16px;
    font-weight: 600;
    border-radius: 6px;
">
    {{ $isClosed ? '📂 Tiket Ditutup' : '📌 Tiket Baru Ditugaskan' }}
</div>
@endcomponent

Assalamualaikum dan Salam Sejahtera,

**Y.Bhg. Tuan/Puan {{ $assignedUser->nama ?? 'N/A' }},**

@if($isClosed)
Tiket aduan ini telah **selesai diproses dan ditutup**. Terima kasih atas **kerjasama tuan/puan**.
@else
Tiket aduan baru telah direkodkan dan ditugaskan kepada anda. **Sila ambil tindakan segera.**
@endif

@component('mail::panel')
## **Maklumat Tiket**

**No. Log** : <strong>{{ $ticket->ticket_nolog }}</strong>

**Tajuk** : <strong>{{ $ticket->ticket_title }}</strong>

**Status** : <strong>{{ $ticket->status->st_name ?? 'N/A' }} [{{ $newStatus }}]</strong>

**Pengadu** : <strong>{{ $ticket->user->nama ?? 'N/A' }}</strong>

**Catatan** : <strong>{{ $ticket->ticket_desc ?? 'Tiada catatan' }}</strong>
@endcomponent

Mohon tindakan selanjutnya melalui pautan di bawah:

@component('mail::button', ['url' => route('tickets.show', $ticket->id)])
Lihat Tiket
@endcomponent

Terima kasih.

**Pentadbir {{ config('app.name') }}**
@endcomponent
{{-- blade-formatter-enable --}}
