@extends('errors.layout')

@section('title', '419 - Sesi Kedaluwarsa')
@section('code', '419')
@section('code_left', '4')
@section('code_mid', '1')
@section('code_right', '9')
@section('bubble_text', '419!')
@section('accent_color', '#b45309')
@section('headline', 'Sesi formulir telah kedaluwarsa.')
@section('description', 'Demi keamanan data dan transaksi kas kelas, token sesi halaman perlu diperbarui. Silakan muat ulang halaman lalu ulangi tindakan Anda.')

@section('actions')
    <button type="button" onclick="window.location.reload()" class="err-btn-primary">
        Muat Ulang Halaman
    </button>
    <a href="{{ url('/') }}" class="err-btn-secondary">
        Kembali ke Beranda
    </a>
@endsection
