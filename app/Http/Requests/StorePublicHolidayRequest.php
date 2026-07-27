<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePublicHolidayRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'holiday_date' => ['required', 'date_format:Y-m-d'],
            'holiday_name' => ['required', 'string', 'min:3', 'max:255'],
            'states' => ['required', 'array', 'min:1'],
            'states.*' => ['string', 'in:national,Johor,Kedah,Kelantan,Melaka,Negeri Sembilan,Pahang,Pulau Pinang,Perak,Perlis,Selangor,Terengganu,Sabah,Sarawak,Wilayah Persekutuan Kuala Lumpur,Wilayah Persekutuan Labuan,Wilayah Persekutuan Putrajaya'],
            'states_except' => ['nullable', 'array'],
            'states_except.*' => ['string', 'in:Johor,Kedah,Kelantan,Melaka,Negeri Sembilan,Pahang,Pulau Pinang,Perak,Perlis,Selangor,Terengganu,Sabah,Sarawak,Wilayah Persekutuan Kuala Lumpur,Wilayah Persekutuan Labuan,Wilayah Persekutuan Putrajaya'],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'holiday_date.required' => 'Sila pilih tarikh',
            'holiday_date.date_format' => 'Format tarikh tidak sah',
            'holiday_date.unique' => 'Cuti umum untuk tarikh ini sudah wujud',
            'holiday_name.required' => 'Sila masukkan nama cuti umum',
            'holiday_name.min' => 'Nama cuti umum sekurang-kurangnya 3 perkataan',
            'holiday_name.max' => 'Nama cuti umum tidak boleh lebih 255 perkataan',
            'states.required' => 'Sila pilih sekurang-kurangnya satu negeri',
            'states.min' => 'Sila pilih sekurang-kurangnya satu negeri',
            'states.*.in' => 'Negeri yang dipilih tidak sah',
            'states_except.*.in' => 'Negeri berkecuali yang dipilih tidak sah',
        ];
    }
}
