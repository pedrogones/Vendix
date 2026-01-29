<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Portal do Cliente')</title>

    @vite('resources/css/app.css')
</head>
<body class="bg-gray-50 dark:bg-gray-900">

<section class="min-h-screen flex items-center justify-center px-6 py-8">
    <div class="w-full max-w-md">
        <div class="flex items-center justify-center mb-6 text-2xl font-semibold text-gray-900 dark:text-white">
            <img class="w-8 h-8 mr-2"
                 src="https://flowbite.s3.amazonaws.com/blocks/marketing-ui/logo.svg"
                 alt="logo">
            Portal do Cliente
        </div>

        <div class="bg-white rounded-lg shadow dark:border dark:bg-gray-800 dark:border-gray-700">
            <div class="p-6 space-y-6 sm:p-8">
                @yield('content')
            </div>
        </div>
    </div>
</section>
@yield('other-scripts')
</body>
</html>
