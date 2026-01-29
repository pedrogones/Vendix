@extends('portal.layout')

@section('title', 'Cadastro')

@section('content')
    <h1 class="text-xl font-bold leading-tight tracking-tight text-gray-900 md:text-2xl dark:text-white">
        Criar conta
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
    <form class="space-y-4" method="POST" action="{{ route('portal.register') }}" autocomplete="off"
    >
        @csrf

        <div>
            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                Nome
            </label>
            <input
                type="text"
                name="name"
                required
                value=""
                placeholder="Digite seu Nome"
                class="bg-gray-50 border border-gray-300 rounded-lg block w-full p-2.5
                   dark:bg-gray-700 dark:border-gray-600 dark:text-white">
        </div>

        <div>
            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                CPF
            </label>
            <input
                type="text"
                name="cpf"
                id="cpf"
                value=""
                required
                placeholder="Digite seu CPF"
                class="bg-gray-50 border border-gray-300 rounded-lg block w-full p-2.5
                   dark:bg-gray-700 dark:border-gray-600 dark:text-white">
        </div>

        <div>
            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                E-mail
            </label>
            <input
                type="email"
                name="email"
                required
                value=""
                autocomplete="new-email"
                placeholder="Digite seu e-mail"
                class="bg-gray-50 border border-gray-300 rounded-lg block w-full p-2.5
                   dark:bg-gray-700 dark:border-gray-600 dark:text-white">
        </div>

        <div>
            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                Senha
            </label>
            <input
                type="password"
                name="password"
                placeholder="Sua senha"
                required
                autocomplete="new-password"
                value=""
                class="bg-gray-50 border border-gray-300 rounded-lg block w-full p-2.5
                   dark:bg-gray-700 dark:border-gray-600 dark:text-white">
        </div>

        <button
            type="submit"
            class="w-full text-white bg-blue-600 hover:bg-blue-700
               font-medium rounded-lg text-sm px-5 py-2.5">
            Cadastrar
        </button>

        <p class="text-sm text-gray-500 dark:text-gray-400 text-center">
            Já tem conta?
            <a href="{{ route('portal.login') }}"
               class="font-medium text-blue-600 hover:underline">
                Entrar
            </a>
        </p>
    </form>
@endsection
@section('other-scripts')
    @include('components.js-components.cpfOrEmailMask')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            cpfOrEmailMask(document.getElementById('cpf'));
        });
    </script>
@endsection
