{{-- blade-formatter-disable --}}
@component('mail::message')
{{-- # ⛔ Tindakan Segera: SLA (OVERDUE) --}}
<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin: 0 0 20px 0;">
    <tr>
        <td style="
            background: #2c2c2c;
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
                ⛔ Tindakan Segera: SLA (OVERDUE)
            </h1>
        </td>
    </tr>
</table>

Assalamualaikum dan Salam Sejahtera,

**Tuan/Puan,**

Perhatian, tiket berikut telah **MELEPASI TARIKH AKHIR (OVERDUE)** tindakan yang ditetapkan dan memerlukan tindakan segera.

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

@component('mail::button', ['url' => route('tickets.show', $ticket->id), 'color' => 'error'])
Lihat Tiket
@endcomponent

Terima kasih.

**Pentadbir {{ config('app.name') }}**
@endcomponent
{{-- blade-formatter-enable --}}
