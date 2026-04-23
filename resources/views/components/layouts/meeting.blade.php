<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Meeting' }} — ProjexFlow</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600|syne:700,800&display=swap" rel="stylesheet"/>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @fluxStyles
</head>
<body class="bg-[#080c14] text-[#dde6f0] overflow-hidden" style="font-family:'Inter',sans-serif">
    {{ $slot }}
    @livewireScripts
    @fluxScripts
</body>
</html>
