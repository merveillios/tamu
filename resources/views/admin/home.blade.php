@extends('layouts.staff')

@section('title', 'Beranda')

@section('content')
    <div class="card border-0 shadow">
        <div class="card-body">
            <h2 class="h5 mb-2">Halo, {{ auth()->user()->name }}</h2>
            <p class="mb-0 text-gray-600">Anda masuk sebagai {{ auth()->user()->role->label() }}. Pilih menu di samping untuk mulai bekerja.</p>
        </div>
    </div>
@endsection
