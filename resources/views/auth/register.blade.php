@extends('layouts.app')
@section('title', 'Cadastro')

@section('content')
<div class="max-w-md mx-auto bg-white p-6 rounded shadow">
    <h2 class="text-2xl font-bold mb-4">Cadastro</h2>
    <form method="POST" action="{{ route('register') }}">
        @csrf
        <input type="text" name="name" placeholder="Nome" class="w-full border p-2 mb-3 rounded" required>
        <input type="email" name="email" placeholder="Email" class="w-full border p-2 mb-3 rounded" required>
        <input type="password" name="password" placeholder="Senha" class="w-full border p-2 mb-3 rounded" required>
        <button class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded w-full">Cadastrar</button>
    </form>
</div>
@endsection
