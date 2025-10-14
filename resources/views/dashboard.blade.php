@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')
<div class="max-w-2xl mx-auto bg-white p-6 rounded shadow">
    <h2 class="text-xl font-bold mb-4">Olá, {{ auth()->user()->name }}</h2>
    <p class="text-lg mb-4">Saldo atual: 
        <span class="font-semibold text-green-600">
            R$ {{ number_format($wallet->balance_cents / 100, 2, ',', '.') }}
        </span>
    </p>

    <div class="flex gap-4">
        <a href="{{ route('deposit.form') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">Fazer Depósito</a>
        <a href="{{ route('transfer.form') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded">Transferir</a>
    </div>
</div>
@endsection
