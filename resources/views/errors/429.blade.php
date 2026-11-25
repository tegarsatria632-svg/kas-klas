@extends('errors.layout')

@section('title', '429 - Terlalu Banyak Permintaan')
@section('code', '429')
@section('code_left', '4')
@section('code_mid', '2')
@section('code_right', '9')
@section('bubble_text', '429!')
@section('accent_color', '#b45309')
@section('headline', 'Terlalu banyak permintaan dalam waktu singkat.')
@section('description', 'Sistem membatasi sementara permintaan dari perangkat Anda demi menjaga keamanan dan kestabilan layanan. Silakan tunggu beberapa saat lalu coba kembali.')

@section('actions')
    <button type="button" onclick="window.location.reload()" class="err-btn-primary">
        Coba Lagi
    </button>
    <a href="{{ url('/') }}" class="err-btn-secondary">
        Kembali ke Beranda
    </a>
@endsection
