<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>{{ $title ?? 'Llama Chat' }}</title>

    <link rel="shortcut icon"
          href="favicon.svg"
          type="image/x-icon">

    @vite(['resources/css/app.css'])
    @fluxAppearance
</head>

<body class="h-screen min-h-screen bg-white dark:bg-zinc-800">

    {{ $slot }}

    @fluxScripts
</body>

</html>
