<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Baris ini mengandungi mesej standard untuk semua rule validation.
    | Boleh diubah mengikut keperluan projek.
    |
    */

    'accepted'             => ':attribute mesti diterima.',
    'active_url'           => ':attribute bukan URL yang sah.',
    'after'                => ':attribute mesti tarikh selepas :date.',
    'after_or_equal'       => ':attribute mesti tarikh selepas atau sama dengan :date.',
    'alpha'                => ':attribute hanya boleh mengandungi huruf.',
    'alpha_dash'           => ':attribute hanya boleh mengandungi huruf, nombor, dan dash.',
    'alpha_num'            => ':attribute hanya boleh mengandungi huruf dan nombor.',
    'array'                => ':attribute mesti array.',
    'before'               => ':attribute mesti tarikh sebelum :date.',
    'before_or_equal'      => ':attribute mesti tarikh sebelum atau sama dengan :date.',
    'between'              => [
        'numeric' => ':attribute mesti antara :min dan :max.',
        'file'    => ':attribute mesti antara :min dan :max kilobyte.',
        'string'  => ':attribute mesti antara :min dan :max aksara.',
        'array'   => ':attribute mesti antara :min dan :max item.',
    ],
    'boolean'              => ':attribute mesti benar atau salah.',
    'confirmed'            => ':attribute pengesahan tidak sepadan.',
    'date'                 => ':attribute bukan tarikh sah.',
    'date_equals'          => ':attribute mesti tarikh sama dengan :date.',
    'date_format'          => ':attribute tidak sepadan dengan format :format.',
    'different'            => ':attribute dan :other mesti berbeza.',
    'digits'               => ':attribute mesti :digits digit.',
    'digits_between'       => ':attribute mesti antara :min dan :max digit.',
    'dimensions'           => ':attribute saiz imej tidak sah.',
    'distinct'             => ':attribute mempunyai nilai berganda.',
    'email'                => ':attribute mesti alamat emel sah.',
    'ends_with'            => ':attribute mesti berakhir dengan salah satu dari: :values.',
    'exists'               => ':attribute terpilih tidak sah.',
    'file'                 => ':attribute mesti fail.',
    'filled'               => ':attribute mesti mempunyai nilai.',
    'gt'                   => [
        'numeric' => ':attribute mesti lebih besar dari :value.',
        'file'    => ':attribute mesti lebih besar dari :value kilobyte.',
        'string'  => ':attribute mesti lebih panjang dari :value aksara.',
        'array'   => ':attribute mesti mempunyai lebih dari :value item.',
    ],
    'gte'                  => [
        'numeric' => ':attribute mesti lebih besar atau sama dengan :value.',
        'file'    => ':attribute mesti lebih besar atau sama dengan :value kilobyte.',
        'string'  => ':attribute mesti lebih panjang atau sama dengan :value aksara.',
        'array'   => ':attribute mesti mempunyai :value item atau lebih.',
    ],
    'image'                => ':attribute mesti imej.',
    'in'                   => ':attribute terpilih tidak sah.',
    'in_array'             => ':attribute tidak wujud dalam :other.',
    'integer'              => ':attribute mesti integer.',
    'ip'                   => ':attribute mesti alamat IP sah.',
    'ipv4'                 => ':attribute mesti alamat IPv4 sah.',
    'ipv6'                 => ':attribute mesti alamat IPv6 sah.',
    'json'                 => ':attribute mesti string JSON sah.',
    'lt'                   => [
        'numeric' => ':attribute mesti kurang dari :value.',
        'file'    => ':attribute mesti kurang dari :value kilobyte.',
        'string'  => ':attribute mesti kurang dari :value aksara.',
        'array'   => ':attribute mesti kurang dari :value item.',
    ],
    'lte'                  => [
        'numeric' => ':attribute mesti kurang atau sama dengan :value.',
        'file'    => ':attribute mesti kurang atau sama dengan :value kilobyte.',
        'string'  => ':attribute mesti kurang atau sama dengan :value aksara.',
        'array'   => ':attribute mesti :value item atau kurang.',
    ],
    'max'                  => [
        'numeric' => ':attribute tidak boleh lebih besar dari :max.',
        'file'    => ':attribute tidak boleh lebih besar dari :max kilobyte.',
        'string'  => ':attribute tidak boleh lebih panjang dari :max aksara.',
        'array'   => ':attribute tidak boleh mempunyai lebih dari :max item.',
    ],
    'mimes'                => ':attribute mesti fail jenis: :values.',
    'mimetypes'            => ':attribute mesti fail jenis: :values.',
    'min'                  => [
        'numeric' => ':attribute mesti sekurang-kurangnya :min.',
        'file'    => ':attribute mesti sekurang-kurangnya :min kilobyte.',
        'string'  => ':attribute mesti sekurang-kurangnya :min aksara.',
        'array'   => ':attribute mesti mempunyai sekurang-kurangnya :min item.',
    ],
    'not_in'               => ':attribute terpilih tidak sah.',
    'not_regex'            => ':attribute format tidak sah.',
    'numeric'              => ':attribute mesti nombor.',
    'password'             => 'Kata laluan tidak sah.',
    'present'              => ':attribute mesti wujud.',
    'regex'                => ':attribute format tidak sah.',
    'required'             => ':attribute diperlukan.',
    'required_if'          => ':attribute diperlukan apabila :other ialah :value.',
    'required_unless'      => ':attribute diperlukan melainkan :other dalam :values.',
    'required_with'        => ':attribute diperlukan apabila :values wujud.',
    'required_with_all'    => ':attribute diperlukan apabila :values wujud.',
    'required_without'     => ':attribute diperlukan apabila :values tidak wujud.',
    'required_without_all' => ':attribute diperlukan apabila tiada :values wujud.',
    'same'                 => ':attribute dan :other mesti sepadan.',
    'size'                 => [
        'numeric' => ':attribute mesti :size.',
        'file'    => ':attribute mesti :size kilobyte.',
        'string'  => ':attribute mesti :size aksara.',
        'array'   => ':attribute mesti mengandungi :size item.',
    ],
    'starts_with'          => ':attribute mesti bermula dengan salah satu dari: :values.',
    'string'               => ':attribute mesti string.',
    'timezone'             => ':attribute mesti zon masa sah.',
    'unique'               => ':attribute telah digunakan.',
    'uploaded'             => ':attribute gagal dimuat naik.',
    'url'                  => ':attribute format tidak sah.',
    'uuid'                 => ':attribute mesti UUID sah.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Tempat untuk mesej validation khusus setiap field.
    | Contoh:
    |
    | 'custom' => [
    |     'email' => [
    |         'required' => 'Sila masukkan email anda.',
    |     ],
    | ],
    |
    */

    'custom' => [
        'title' => [
            'required' => 'Tajuk diperlukan.',
            'string' => 'Tajuk mesti teks.',
            'max' => 'Tajuk tidak boleh lebih 255 aksara.',
        ],
        'message' => [
            'string' => 'Mesej mesti teks.',
            'max' => 'Mesej tidak boleh lebih 5000 aksara.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | Tukar nama field kepada lebih mesra pengguna dalam mesej error.
    |
    */

    'attributes' => [
        'title' => 'tajuk',
        'message' => 'mesej',
        'email' => 'emel',
        'password' => 'kata laluan',
    ],

];
