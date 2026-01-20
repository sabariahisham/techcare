{{-- blade-formatter-disable --}}
@component('mail::message')
{{-- # 📌 Tiket Baru Ditugaskan --}}
<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin: 0 0 20px 0;">
    <tr>
        <td style="
            background: #ffab00;   
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
                text-shadow: 0 1px 2px rgba(0,0,0,0.15);
            ">
                📌 Tiket Baru Ditugaskan
            </p>
        </td>
    </tr>
</table>

Assalamualaikum dan Salam Sejahtera,

**Y.Bhg. Tuan/Puan {{ $assignedUser->nama ?? 'N/A' }},**

Tiket aduan baru telah direkodkan dan ditugaskan kepada anda. **Sila ambil tindakan segera.**

@component('mail::panel')
## **Maklumat Tiket**

**No. Log** : <strong>{{ $ticket->ticket_nolog }}</strong>

**Tajuk** : <strong>{{ $ticket->ticket_title }}</strong>

**Status** : <strong>{{ $ticket->status->st_name ?? 'N/A' }}</strong>

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
