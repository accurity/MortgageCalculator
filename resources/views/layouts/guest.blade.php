<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }} — beheer</title>

        <link rel="stylesheet" href="{{ asset('assets/css/admin.css') }}">
    </head>
    <body>
        <div class="guest-wrap">
            <a href="/" class="guest-brand">{{ config('app.name', 'Laravel') }}</a>

            <div class="guest-card">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
