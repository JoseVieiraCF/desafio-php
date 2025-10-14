@extends('layouts.app')
@section('title', 'Histórico')

@section('content')
<div class="max-w-3xl mx-auto bg-white p-6 rounded shadow">
    <h2 class="text-xl font-bold mb-4">Histórico de Transações</h2>
    <table class="w-full border-collapse">
        <thead>
            <tr class="border-b bg-gray-100">
                <th class="p-2 text-left">Tipo</th>
                <th class="p-3">De</th>
                <th class="p-3">Para</th>
                <th class="p-2 text-left">Valor</th>
                <th class="p-2 text-left">Data</th>
                <th class="p-2 text-left">Ações</th>
            </tr>
        </thead>
        <tbody>
            @foreach($transactions as $tx)
                <tr class="border-b">
                    <td class="p-2">{{ ucfirst($tx->type) }}</td>
                    <td class="p-2">{{ $tx->fromUser?->name ?? '-' }}</td>
                    <td class="p-2">{{ $tx->toUser?->name ?? '-' }}</td>
                    <td class="p-2">R$ {{ number_format($tx->amount_cents / 100, 2, ',', '.') }}</td>
                    <td class="p-2">{{ $tx->created_at->format('d/m/Y H:i') }}</td>
                    <td class="p-2">
                        @if($tx->status !== 'reversed' && Auth::id() === $tx->fromUser?->id && $tx->type !== 'reversal')
                        <form action="{{ route('transactions.reverse', $tx->id) }}" method="POST">
                            @csrf
                            <button class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded">Reverter</button>
                        </form>
                        @else
                        <span class="text-gray-500 italic">{{$tx->status}}</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
        
    </table>
        
</div>
@endsection
