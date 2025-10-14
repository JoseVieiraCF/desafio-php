@extends('layouts.app')
@section('title', 'Transferência')

@section('content')
<div class="max-w-md mx-auto bg-white p-6 rounded shadow">
    <h2 class="text-xl font-bold mb-4">Transferir Saldo</h2>
    <form action="{{ route('transfer') }}" method="POST">
        @csrf
        <input type="hidden" name="idempotency_key" id="idempotency_key">
        <label class="block mb-2">Destinatário (Email)</label>
        <input type="email" name="email" class="border w-full p-2 mb-3 rounded" required>

        <label class="block mb-2">Valor (R$)</label>
        <input type="number" step="0.01" min="0.01" name="amount" class="border w-full p-2 mb-3 rounded" required>

        <button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded w-full">Enviar</button>
    </form>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.getElementById('idempotency_key').value = generateUUID();
        });
    </script>
@endpush
@endsection
