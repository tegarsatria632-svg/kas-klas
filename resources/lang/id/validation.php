<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Baris Bahasa Validasi
    |--------------------------------------------------------------------------
    |
    | Baris bahasa berikut berisi pesan kesalahan standar yang digunakan oleh
    | kelas validator. Beberapa aturan ini memiliki beberapa versi seperti
    | aturan ukuran. Silakan ubah setiap pesan ini di sini.
    |
    */

    'accepted' => ':attribute harus diterima.',
    'accepted_if' => ':attribute harus diterima saat :other adalah :value.',
    'active_url' => ':attribute bukan URL yang valid.',
    'after' => ':attribute harus berupa tanggal setelah :date.',
    'after_or_equal' => ':attribute harus berupa tanggal setelah atau sama dengan :date.',
    'alpha' => ':attribute hanya boleh berisi huruf.',
    'alpha_dash' => ':attribute hanya boleh berisi huruf, angka, setrip, dan garis bawah.',
    'alpha_num' => ':attribute hanya boleh berisi huruf dan angka.',
    'array' => ':attribute harus berupa sebuah array.',
    'before' => ':attribute harus berupa tanggal sebelum :date.',
    'before_or_equal' => ':attribute harus berupa tanggal sebelum atau sama dengan :date.',
    'between' => [
        'numeric' => ':attribute harus bernilai antara :min dan :max.',
        'file' => ':attribute harus berukuran antara :min dan :max kilobita.',
        'string' => ':attribute harus berisi antara :min dan :max karakter.',
        'array' => ':attribute harus memiliki antara :min dan :max item.',
    ],
    'boolean' => ':attribute harus bernilai benar atau salah.',
    'confirmed' => 'Konfirmasi :attribute tidak cocok.',
    'current_password' => 'Kata sandi saat ini tidak tepat.',
    'date' => ':attribute bukan tanggal yang valid.',
    'date_equals' => ':attribute harus berupa tanggal yang sama dengan :date.',
    'date_format' => ':attribute tidak cocok dengan format :format.',
    'declined' => ':attribute harus ditolak.',
    'declined_if' => ':attribute harus ditolak ketika :other adalah :value.',
    'different' => ':attribute dan :other harus berbeda.',
    'digits' => ':attribute harus terdiri dari :digits angka.',
    'digits_between' => ':attribute harus bernilai antara :min dan :max angka.',
    'dimensions' => ':attribute memiliki dimensi gambar yang tidak valid.',
    'distinct' => ':attribute memiliki nilai duplikat.',
    'email' => ':attribute harus berupa alamat surel yang valid.',
    'ends_with' => ':attribute harus diakhiri dengan salah satu dari: :values.',
    'enum' => ':attribute yang dipilih tidak valid.',
    'exists' => ':attribute yang dipilih tidak valid.',
    'file' => ':attribute harus berupa sebuah berkas.',
    'filled' => ':attribute harus memiliki nilai.',
    'gt' => [
        'numeric' => ':attribute harus bernilai lebih besar dari :value.',
        'file' => ':attribute harus berukuran lebih besar dari :value kilobita.',
        'string' => ':attribute harus berisi lebih dari :value karakter.',
        'array' => ':attribute harus memiliki lebih dari :value item.',
    ],
    'gte' => [
        'numeric' => ':attribute harus bernilai lebih besar dari atau sama dengan :value.',
        'file' => ':attribute harus berukuran lebih besar dari atau sama dengan :value kilobita.',
        'string' => ':attribute harus berisi minimal :value karakter.',
        'array' => ':attribute harus memiliki minimal :value item.',
    ],
    'image' => ':attribute harus berupa gambar.',
    'in' => ':attribute yang dipilih tidak valid.',
    'in_array' => ':attribute tidak ada di dalam :other.',
    'integer' => ':attribute harus berupa bilangan bulat.',
    'ip' => ':attribute harus berupa alamat IP yang valid.',
    'ipv4' => ':attribute harus berupa alamat IPv4 yang valid.',
    'ipv6' => ':attribute harus berupa alamat IPv6 yang valid.',
    'json' => ':attribute harus berupa string JSON yang valid.',
    'lt' => [
        'numeric' => ':attribute harus bernilai kurang dari :value.',
        'file' => ':attribute harus berukuran kurang dari :value kilobita.',
        'string' => ':attribute harus berisi kurang dari :value karakter.',
        'array' => ':attribute harus memiliki kurang dari :value item.',
    ],
    'lte' => [
        'numeric' => ':attribute harus bernilai kurang dari atau sama dengan :value.',
        'file' => ':attribute harus berukuran kurang dari atau sama dengan :value kilobita.',
        'string' => ':attribute tidak boleh lebih dari :value karakter.',
        'array' => ':attribute tidak boleh memiliki lebih dari :value item.',
    ],
    'max' => [
        'numeric' => ':attribute tidak boleh lebih dari :max.',
        'file' => ':attribute tidak boleh lebih dari :max kilobita (2 MB).',
        'string' => ':attribute tidak boleh lebih dari :max karakter.',
        'array' => ':attribute tidak boleh memiliki lebih dari :max item.',
    ],
    'mimes' => ':attribute harus berupa berkas bertipe: :values.',
    'mimetypes' => ':attribute harus berupa berkas bertipe: :values.',
    'min' => [
        'numeric' => ':attribute minimal bernilai :min.',
        'file' => ':attribute minimal berukuran :min kilobita.',
        'string' => ':attribute minimal harus terdiri dari :min karakter.',
        'array' => ':attribute minimal harus memiliki :min item.',
    ],
    'multiple_of' => ':attribute harus merupakan kelipatan dari :value.',
    'not_in' => ':attribute yang dipilih tidak valid.',
    'not_regex' => 'Format :attribute tidak valid.',
    'numeric' => ':attribute harus berupa angka.',
    'password' => 'Kata sandi tidak tepat.',
    'present' => ':attribute harus ada.',
    'prohibited' => ':attribute tidak diizinkan.',
    'prohibited_if' => ':attribute tidak diizinkan saat :other adalah :value.',
    'prohibited_unless' => ':attribute tidak diizinkan kecuali :other ada di dalam :values.',
    'prohibits' => ':attribute melarang :other untuk ada.',
    'regex' => 'Format :attribute tidak valid.',
    'required' => ':attribute wajib diisi.',
    'required_array_keys' => ':attribute harus berisi entri untuk: :values.',
    'required_if' => ':attribute wajib diisi bila :other adalah :value.',
    'required_unless' => ':attribute wajib diisi kecuali :other memiliki nilai :values.',
    'required_with' => ':attribute wajib diisi bila terdapat :values.',
    'required_with_all' => ':attribute wajib diisi bila terdapat :values.',
    'required_without' => ':attribute wajib diisi bila tidak terdapat :values.',
    'required_without_all' => ':attribute wajib diisi bila sama sekali tidak terdapat :values.',
    'same' => ':attribute dan :other harus sama.',
    'size' => [
        'numeric' => ':attribute harus berukuran :size.',
        'file' => ':attribute harus berukuran :size kilobyte.',
        'string' => ':attribute harus berukuran :size karakter.',
        'array' => ':attribute harus mengandung :size item.',
    ],
    'starts_with' => ':attribute harus diawali salah satu dari: :values.',
    'string' => ':attribute harus berupa teks / karakter string.',
    'timezone' => ':attribute harus berupa zona waktu yang valid.',
    'unique' => ':attribute sudah terdaftar dan digunakan.',
    'uploaded' => ':attribute gagal diunggah.',
    'url' => ':attribute harus berupa format URL yang valid.',
    'uuid' => ':attribute harus berupa UUID yang valid.',

    /*
    |--------------------------------------------------------------------------
    | Nama Atribut Kustom
    |--------------------------------------------------------------------------
    |
    | Baris berikut digunakan untuk menukar 'placeholder' atribut kami dengan
    | sesuatu yang lebih ramah pembaca seperti "Alamat Surel" daripada "email".
    |
    */

    'attributes' => [
        'name' => 'Nama lengkap',
        'npm' => 'NPM / Username',
        'username' => 'Username',
        'email' => 'Alamat email',
        'password' => 'Kata sandi',
        'current_password' => 'Kata sandi saat ini',
        'new_password' => 'Kata sandi baru',
        'new_password_confirmation' => 'Konfirmasi kata sandi baru',
        'role' => 'Peran akun',
        'semester' => 'Semester',
        'status_akademik' => 'Status akademik',
        'tahun_ajaran' => 'Tahun ajaran',
        'tahun_ajaran_baru' => 'Tahun ajaran baru',
        'type' => 'Jenis transaksi',
        'amount' => 'Nominal transaksi',
        'amount_per_week' => 'Tarif iuran mingguan',
        'date' => 'Tanggal transaksi',
        'description' => 'Keterangan transaksi',
        'proof_image' => 'Foto bukti nota',
        'user_id' => 'Nama mahasiswa',
        'weeks' => 'Pilihan minggu',
        'week_number' => 'Minggu ke',
        'month' => 'Bulan',
        'year' => 'Tahun',
        'q' => 'Kata kunci pencarian',
    ],
];
