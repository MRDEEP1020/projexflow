<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'ProjexFlow') }} — {{ $title ?? 'Sign In' }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Mono:wght@300;400;500&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    <style>
        :root {
            --bg:        #080c14;
            --surface:   #0e1420;
            --surface2:  #131d2e;
            --border:    #1c2e45;
            --border2:   #254060;
            --accent:    #7EE8A2;
            --accent2:   #00ff94;
            --text:      #dde6f0;
            --dim:       #8da0b8;
            --muted:     #506070;
        }

        *, *::before, *::after { box-sizing: border-box; }

        html, body {
            margin: 0; padding: 0;
            min-height: 100vh;
            background: var(--bg);
            color: var(--text);
            font-family: 'Inter', sans-serif;
            -webkit-font-smoothing: antialiased;
        }
    </style>
</head>
<body>
    {{ $slot }}

    @livewireScripts
</body>
</html>
