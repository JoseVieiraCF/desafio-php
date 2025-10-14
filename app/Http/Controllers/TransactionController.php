<?php

namespace App\Http\Controllers;

use App\Exceptions\AlreadyReversedException;
use App\Exceptions\InsufficientFundsException;
use App\Models\Transaction;
use App\Services\TransactionService;
use Illuminate\Support\Facades\Auth;

class TransactionController extends Controller
{
    private TransactionService $service;

    public function __construct(TransactionService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        $transactions = Transaction::with(['fromUser', 'toUser'])->where('from_user_id', Auth::id())
            ->orWhere('to_user_id', Auth::id())
            ->orderByDesc('id')
            ->paginate(10);

        return view('transactions', compact('transactions'));
    }

    public function reverse($id)
    {
        try {
            $this->service->reverseTransaction($id, Auth::user());
            return redirect()->route('dashboard')->with('success', 'Transação revertida com sucesso.');
        } catch (InsufficientFundsException $e) {
            return back()->withErrors(['erro' => 'Erro ao reverter: '.$e->getMessage()]);
        } catch (AlreadyReversedException $e) {
            return back()->withErrors(['erro' => 'Erro ao reverter: '.$e->getMessage()]);
        } catch (\Exception $e) {
            return back()->withErrors(['erro' => 'Erro ao reverter a transação:'.$e->getMessage()]);
        }
    }
}
