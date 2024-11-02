<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>
    <link rel="icon" type="image/png" href="../../assets/img/favicon.ico">
    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700|Noto+Sans:300,400,500,600,700,800|PT+Mono:300,400,500,600,700" rel="stylesheet" />
    <script src="https://kit.fontawesome.com/349ee9c857.js" crossorigin="anonymous"></script>
    <!--     Fonts and icons     -->
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700" rel="stylesheet" />
    <!-- Nucleo Icons -->
    <link href="../assets/css/nucleo-icons.css" rel="stylesheet" />
    <link href="../assets/css/nucleo-svg.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.min.css">
    <!-- CSS Files -->
    <link id="pagestyle" href="../assets/css/corporate-ui-dashboard.css?v=1.0.0" rel="stylesheet" />
    <!-- Scripts -->
    <!--<script src="https://rawgit.com/schmich/instascan-builds/master/instascan.min.js">-->
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
</head>

<body>
    <div>
        @guest
        {{-- El navbar no se mostrará si no se ha iniciado sesión --}}
        @else
        @include('layouts.menu')
        @endguest

        @yield('content')
        <script>
            @auth
              window.Permissions = {!! json_encode(Auth::user()->allPermissions, true) !!};
            @else
              window.Permissions = [];
            @endauth
        </script>
    </div>
    @include('layouts.footer')
</body>
</html>
