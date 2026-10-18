<!DOCTYPE html>
<html lang="id">
<head>
    @include('partials.head')
</head>
<body class="bg-white">
    <main class="container py-4">
        @yield('content')
    </main>

    @include('partials.scripts')
</body>
</html>
