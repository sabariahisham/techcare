{{-- blade-formatter-disable --}}
@component('mail::message')
{{-- Dynamic header based on status --}}
@php
    $isClosed = (int) $ticket->tick_status === 6;
@endphp

@component('mail::panel')
{{-- Aduan Selesai(6) background Hijau & text berubah --}}
<div style="
    background: {{ $isClosed ? '#2e7d32' : '#696cff' }};
    color: #ffffff;
    padding: 14px;
    text-align: center;
    font-size: 16px;
    font-weight: 600;
    border-radius: 6px;
">
    {{ $isClosed ? '📂 Tiket Ditutup' : '🔔 Status Aduan' }}
</div>
@endcomponent

Assalamualaikum dan Salam Sejahtera, 

**Y.Bhg.Tan Sri/Puan Sri/Datuk/Dato/Datin/Tuan/Puan,**

@if($isClosed)
Tiket aduan ini telah **selesai diproses dan ditutup**. Terima kasih atas **kerjasama tuan/puan**.
@else
Tiket aduan telah berjaya direkodkan dan akan diproses.
@endif

@component('mail::panel')
## **Maklumat Tiket**

**No. Log** : <strong>{{ $ticket->ticket_nolog }}</strong>

**Tajuk** : <strong>{{ $ticket->ticket_title }}</strong>

**Status** : <strong>{{ $statusName ?? ($ticket->status->st_name ?? 'N/A') }}</strong>

**Pegawai Tugasan** : <strong>{{ $assignedUser->nama ?? 'Belum Ditugaskan' }}</strong>
@endcomponent

Sila semak status tiket melalui pautan berikut:

@component('mail::button', ['url' => route('tickets.show', $ticket->id)])
Lihat Tiket
@endcomponent

Terima kasih.

**Pentadbir {{ config('app.name') }}**
@endcomponent
{{-- blade-formatter-enable --}}
