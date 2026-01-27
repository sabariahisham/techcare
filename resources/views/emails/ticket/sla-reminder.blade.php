{{-- blade-formatter-disable --}}
@component('mail::message')
{{-- Header based on reminder type --}}
<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin: 0 0 20px 0;">
    <tr>
        <td style="
            background: {{ $reminderType === 'due_today' ? '#d97706' : '#2563eb' }};
            padding: 14px 18px;
            border-radius: 8px;
            text-align: center;
        ">
            <h1 style="
                color: #ffffff;
                font-size: 16px;
                font-weight: 600;
                margin: 0;
                text-align: center;
                text-shadow: 0 1px 2px rgba(0,0,0,0.15);
            ">
                @if($reminderType === 'before_due')
                    ⚠️ Peringatan SLA - Hampir Tamat Tempoh
                @elseif($reminderType === 'due_today')
                    🔔 Peringatan SLA - Tamat Tempoh HARI INI
                @elseif($reminderType === 'after_due')
                    ⚠️ Peringatan SLA - Tempoh Tamat Semalam
                @else
                    ⚠️ Peringatan SLA
                @endif
            </h1>
        </td>
    </tr>
</table>

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

@if($reminderType === 'before_due')
Tiket ini akan tamat tempoh **esok**. Mohon tuan/puan mengambil tindakan segera untuk mengelakkan tiket daripada menjadi overdue.
@elseif($reminderType === 'due_today')
**PERHATIAN:** Tiket ini akan tamat tempoh pada **HARI INI**. Sila selesaikan tiket ini sebelum tamat waktu bekerja.
@elseif($reminderType === 'after_due')
**MAKLUMAN:** Tiket ini telah melepasi tarikh akhir **semalam**. Tindakan segera diperlukan untuk menyelesaikan isu ini.
@endif

Sila akses tiket melalui pautan berikut:

@component('mail::button', ['url' => route('tickets.show', $ticket->uuid), 'color' => $reminderType === 'due_today' ? 'error' : 'primary'])
Lihat Tiket
@endcomponent

Terima kasih atas perhatian dan kerjasama tuan/puan.

**Pentadbir {{ config('app.name') }}**
@endcomponent
{{-- blade-formatter-enable --}}
