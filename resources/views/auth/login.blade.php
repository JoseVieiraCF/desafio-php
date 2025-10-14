@extends('layouts.app')
@section('title', 'Login')

@section('content')
<div class="max-w-md mx-auto bg-white p-6 rounded shadow">
    <h2 class="text-2xl font-bold mb-4">Entrar</h2>
    <form method="POST" action="{{ route('login') }}">
        @csrf
        <input type="email" name="email" placeholder="Email" class="w-full border p-2 mb-3 rounded" required>
        <input type="password" name="password" placeholder="Senha" class="w-full border p-2 mb-3 rounded" required>
        <button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded w-full">Entrar</button>
    </form>
</div>
@endsection
