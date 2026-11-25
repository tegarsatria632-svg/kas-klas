@extends('errors.layout')

@section('title', '500 - Gangguan Server')
@section('code', '500')
@section('code_left', '5')
@section('code_mid', '0')
@section('code_right', '0')
@section('bubble_text', '500!')
@section('accent_color', '#b91c1c')
@section('headline', 'Maaf, terjadi gangguan sementara pada server.')
@section('description', 'Permintaan Anda belum dapat diproses saat ini. Silakan coba muat ulang halaman beberapa saat lagi atau kembali ke beranda.')

@section('actions')
    <button type="button" onclick="window.location.reload()" class="err-btn-primary">
        Coba Muat Ulang
    </button>
    <a href="/" class="err-btn-secondary">
        Kembali ke Beranda
    </a>
@endsection
