<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Carteira')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 text-gray-800">
    <nav class="bg-blue-600 text-white p-4 flex justify-between">
        <div class="font-bold">💳 Carteira Financeira</div>
        <div>
            @guest
            <a href="{{ route('register.form') }}" class="mx-2">Cadastrar</a>
            @endguest
            @auth
                <a href="{{ route('dashboard') }}" class="mx-2">Dashboard</a>
                <a href="{{ route('deposit.form') }}" class="mx-2">Depósito</a>
                <a href="{{ route('transfer.form') }}" class="mx-2">Transferência</a>
                <a href="{{ route('transactions.index') }}" class="mx-2">Histórico</a>
                <form action="{{ route('logout') }}" method="POST" class="inline">
                    @csrf
                    <button class="ml-4 bg-red-500 hover:bg-red-700 text-white px-3 py-1 rounded">Sair</button>
                </form>
            @endauth
        </div>
    </nav>

    <div class="container mx-auto p-6">
        @if (session('success'))
            <div class="bg-green-200 border border-green-400 text-green-800 px-4 py-2 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->has('erro'))
            <div class="bg-red-200 border border-red-400 text-red-800 px-4 py-2 rounded mb-4">
                {{ $errors->first('erro') }}
            </div>
        @endif

        @yield('content')
    </div>
    @stack('scripts')
</body>
</html>
