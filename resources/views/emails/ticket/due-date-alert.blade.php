{{-- blade-formatter-disable --}}
@component('mail::message')
{{-- # ⚠️ Peringatan Tarikh Tamat Tindakan --}}
<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin: 0 0 20px 0;">
    <tr>
        <td style="
            background: #ff3e1d;
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
                ⚠️ Peringatan Segera: Tarikh Tamat Tindakan
            </h1>
        </td>
    </tr>
</table>

Assalamualaikum dan Salam Sejahtera,

**Tuan/Puan,**

Ini adalah peringatan bahawa tiket berikut menghampiri **TARIKH AKHIR** tindakan.

@component('mail::panel')
## **Maklumat Tiket**

**No. Log** : <strong>{{ $ticket->ticket_nolog }}</strong>

**Tajuk** : <strong>{{ $ticket->ticket_title }}</strong>

**Tarikh Akhir** : <strong>{{ $ticket->tick_duedate ? \Carbon\Carbon::parse($ticket->tick_duedate)->format('d/m/Y') : 'Tiada' }}</strong>

**Status** : <strong>{{ $ticket->status->st_name ?? 'N/A' }}</strong>
@endcomponent

Mohon tuan/puan mengambil tindakan sewajarnya sebelum **TARIKH AKHIR** bagi memastikan penyelesaian dapat dilaksanakan dalam tempoh ditetapkan.

Sila akses tiket melalui pautan berikut:

@component('mail::button', ['url' => route('tickets.show', $ticket->id), 'color' => 'error'])
Lihat Tiket
@endcomponent

Terima kasih.

**Pentadbir {{ config('app.name') }}**
@endcomponent
{{-- blade-formatter-enable --}}
