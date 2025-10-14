@extends('layouts.app')
@section('title', 'Depósito')

@section('content')
<div class="max-w-md mx-auto bg-white p-6 rounded shadow">
    <h2 class="text-xl font-bold mb-4">Fazer Depósito</h2>
    <form class="transaction" action="{{ route('deposit') }}" method="POST">
        @csrf
        <input type="hidden" name="idempotency_key" id="idempotency_key">
        <label class="block mb-2">Valor (R$)</label>
        <input type="number" step="0.01" min="0.01" name="amount" class="border w-full p-2 mb-3 rounded" required>
        <button class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded w-full">Depositar</button>
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
