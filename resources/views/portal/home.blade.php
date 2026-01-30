<!doctype html>
<html lang="pt-BR" class="h-full scroll-smooth">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>{{ config('app.name', 'Vendix') }}</title>
    <meta name="title" content="{{ config('app.name', 'Vendix') }}" />
    <meta name="description" content="Sistema de vendas, estoque e financeiro em um unico lugar." />
    <meta name="application-name" content="{{ config('app.name', 'Vendix') }}" />
    <meta name="theme-color" content="#5b3df5" />
    <meta name="apple-mobile-web-app-title" content="{{ config('app.name', 'Vendix') }}" />
    <meta name="apple-mobile-web-app-capable" content="yes" />
    <meta name="mobile-web-app-capable" content="yes" />
    <meta name="format-detection" content="telephone=no" />
    <link rel="canonical" href="{{ config('app.url', url('/')) }}" />

{{--    <link rel="icon" href="{{ asset('assets/img/vendix.png') }}"  />--}}
{{--    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('assets/favicon-32x32.png') }}" />--}}
{{--    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('assets/favicon-16x16.png') }}" />--}}
{{--    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('assets/apple-touch-icon.png') }}" />--}}

{{--<link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">--}}
{{--<link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">--}}
{{--<link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">--}}

{{--    <meta property="og:type" content="website" />--}}
{{--    <meta property="og:site_name" content="{{ config('app.name', 'Vendix') }}" />--}}
{{--    <meta property="og:title" content="{{ config('app.name', 'Vendix') }}" />--}}
{{--    <meta property="og:description" content="Sistema de vendas, estoque e financeiro em um unico lugar." />--}}
{{--    <meta property="og:url" content="{{ config('app.url', url('/')) }}" />--}}
{{--    <meta property="og:image" content="{{ asset('assets/img/vendix_logo.png') }}" />--}}
{{--    <meta name="twitter:card" content="summary_large_image" />--}}
{{--    <meta name="twitter:title" content="{{ config('app.name', 'Vendix') }}" />--}}
{{--    <meta name="twitter:description" content="Sistema de vendas, estoque e financeiro em um unico lugar." />--}}
{{--    <meta name="twitter:image" content="{{ asset('assets/img/vendix_logo.png') }}" />--}}
{{--    <link rel="preconnect" href="https://fonts.googleapis.com">--}}
{{--    <link rel="preconnect" href="https://fonts.googleapis.com">--}}
{{--    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>--}}
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root {
            color-scheme: light;
        }
        body {
            font-family: 'Manrope', sans-serif;
        }
        .animate-fade-up {
            animation: fadeUp 0.8s ease both;
        }
        .animate-float {
            animation: float 6s ease-in-out infinite;
        }
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(18px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
    </style>
</head>
<body class="min-h-full bg-white text-slate-900 antialiased">
{{--    @include('portal.components.topbar')--}}
    @include('portal.components.navbar')
    @include('portal.components.hero')
    @include('portal.components.trust')
    @include('portal.components.features')
    @include('portal.components.segments')
    @include('portal.components.pricing')
    @include('portal.components.cta')
    @include('portal.components.faq')
    @include('portal.components.footer')
</body>
</html>
