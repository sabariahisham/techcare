{{-- blade-formatter-disable --}}
@component('mail::message')
<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin: 0 0 20px 0;">
    <tr>
        <td style="
            background: #696cff;
            padding: 14px 18px;
            border-radius: 8px;
            text-align: center;
        ">
            <p style="
                color: #ffffff;
                font-size: 16px;
                font-weight: 600;
                margin: 0;
                text-align: center;                
            ">
                🔔 Status Aduan
            </p>
        </td>
    </tr>    
</table>


Assalamualaikum dan Salam Sejahtera,

**Y.Bhg.Tan Sri/Puan Sri/Datuk/Dato/Datin/Tuan/Puan,**

Tiket aduan telah berjaya direkodkan dan akan diproses.

@component('mail::panel')
## **Maklumat Tiket**

**No. Log** : <strong>{{ $ticket->ticket_nolog }}</strong>

**Tajuk** : <strong>{{ $ticket->ticket_title }}</strong>

**Status Semasa** : <strong>{{ $ticket->status->st_name ?? 'N/A' }}</strong>

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
