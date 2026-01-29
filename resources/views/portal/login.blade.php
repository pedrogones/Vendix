@extends('portal.layout')

@section('title', 'Login')

@section('content')
    <h1 class="text-xl font-bold leading-tight tracking-tight text-gray-900 md:text-2xl dark:text-white">
        Entrar na conta
    </h1>
    @if ($errors->any())
        <div class="mb-4 rounded-lg bg-red-50 border border-red-300 text-red-700 p-4">
            <ul class="text-sm list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form class="space-y-4" method="POST" action="{{ route('portal.login') }}">
        @csrf

        <div>
            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                E-mail ou CPF
            </label>
            <input
                type="text"
                id="loginInput"
                name="login"
                placeholder="CPF ou e-mail"
                required
                class="bg-gray-50 border border-gray-300 text-gray-900 rounded-lg block w-full p-2.5
           dark:bg-gray-700 dark:border-gray-600 dark:text-white">

        </div>

        <div>
            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                Senha
            </label>
            <input
                type="password"
                name="password"
                required
                class="bg-gray-50 border border-gray-300 text-gray-900 rounded-lg block w-full p-2.5
                   dark:bg-gray-700 dark:border-gray-600 dark:text-white">
        </div>

        <button
            type="submit"
            class="w-full text-white bg-blue-600 hover:bg-blue-700
               font-medium rounded-lg text-sm px-5 py-2.5">
            Entrar
        </button>

            <p class="text-sm text-gray-500 dark:text-gray-400 text-center">
            Não tem conta?
            <a href="{{ route('portal.register') }}"
               class="font-medium text-blue-600 hover:underline">
                Criar conta
            </a>
        </p>
    </form>
@endsection
@section('other-scripts')
    @include('components.js-components.cpfOrEmailMask')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            cpfOrEmailMask(document.getElementById('loginInput'));
        });
    </script>
@endsection

