<!DOCTYPE html x-data="themeHandler()" x-init="init()">
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="stylesheet" href="https://use.typekit.net/pdd8jng.css">

        <!-- Favicon -->
        <link rel="icon" type="image/png" href="favicon/favicon-96x96.png" sizes="96x96" />
        <link rel="icon" type="image/svg+xml" href="favicon/favicon.svg" />
        <link rel="shortcut icon" href="favicon/favicon.ico" />
        <link rel="apple-touch-icon" sizes="180x180" href="favicon/apple-touch-icon.png" />
        <meta name="apple-mobile-web-app-title" content="Intranet" />
        <link rel="manifest" href="favicon/site.webmanifest" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans bg-[--color-background] antialiased">

        <x-setting/>

        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-[--color-background]">
            <div>
                <a href="/">
                    <x-application-logo class="w-[250px] h-auto" />
                </a>
            </div>

            <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-[--color-card] shadow-md overflow-hidden sm:rounded-lg">
                {{ $slot }}
            </div>
        </div>

    </body>
</html>
