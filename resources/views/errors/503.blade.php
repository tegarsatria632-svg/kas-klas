@extends('errors.layout')

@section('title', '503 - Layanan Sedang Pemeliharaan')
@section('code', '503')
@section('code_left', '5')
@section('code_mid', '0')
@section('code_right', '3')
@section('bubble_text', '503!')
@section('accent_color', '#3143da')
@section('headline', 'Sistem sedang dalam pemeliharaan singkat.')
@section('description', 'Kami sedang melakukan pembaruan agar layanan Kas Kelas Informatika UPS Tegal semakin stabil. Silakan muat ulang dalam beberapa menit.')

@section('actions')
    <button type="button" onclick="window.location.reload()" class="err-btn-primary">
        Muat Ulang Halaman
    </button>
    <a href="/" class="err-btn-secondary">
        Kembali ke Beranda
    </a>
@endsection
