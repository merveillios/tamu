<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@hasSection('title')@yield('title') · @endif{{ config('app.name') }}</title>
<link rel="icon" type="image/png" href="{{ asset('assets/img/logo.png') }}">
<link rel="stylesheet" href="{{ asset('assets/vendor/inter/inter.css') }}">
<link rel="stylesheet" href="{{ asset('assets/vendor/volt/css/volt.css') }}">
<link rel="stylesheet" href="{{ asset('assets/css/app.css') }}?v={{ filemtime(public_path('assets/css/app.css')) }}">
@stack('styles')
