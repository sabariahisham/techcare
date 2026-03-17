{{-- blade-formatter-disable --}}
@component('mail::message')
{{-- Dynamic header based on status --}}
{{-- @php
    //$isClosed = (int) $ticket->tick_status === 6;
    $isClosed = (int) ($ticket->tick_status ?? $ticket->status_id) === 6;
@endphp --}}
@php
    $isClosed = $statusName === 'Aduan Selesai';
@endphp

{{-- @component('mail::panel') --}}
{{-- Aduan Selesai(6) background Hijau & text berubah --}}
<div style="
    background: {{ $isClosed ? '#2e7d32' : '#696cff' }};
    color: #ffffff;
    padding: 14px;
    text-align: center;
    font-size: 16px;
    font-weight: 600;
    border-radius: 6px;
    margin-bottom: 18px;
">
    {{ $isClosed ? '📂 Tiket Ditutup' : '🔔 Status Aduan' }}
</div>
{{-- @endcomponent --}}

Assalamualaikum dan Salam Sejahtera, 

**Y.Bhg.Tan Sri/Puan Sri/Datuk/Dato/Datin/Tuan/Puan,**

@if($isClosed)
Tiket aduan ini telah **selesai diproses dan ditutup**. Terima kasih atas **kerjasama tuan/puan**.
@else
Tiket aduan telah berjaya direkodkan dan akan diproses.
@endif

@component('mail::panel')
## **Maklumat Tiket**

**No. Log** : <strong>{{ $ticket->ticket_nolog }}</strong><br>
**Tajuk** : <strong>{{ $ticket->ticket_title }}</strong><br>
**Status** : <strong>{{ $statusName ?? ($ticket->status->st_name) }}</strong><br>
**Pegawai Tugasan** : <strong>{{ $assignedUser->nama ?? 'Tiada rekod' }}</strong>
@endcomponent

Sila semak status tiket melalui pautan berikut:

@component('mail::button', ['url' => route('tickets.show', $ticket->uuid)])
Lihat Tiket
@endcomponent

Terima kasih.

**Pentadbir {{ config('app.name') }}**
@endcomponent
{{-- blade-formatter-enable --}}
