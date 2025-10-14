<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientFundsException;
use App\Exceptions\InvalidValueException;
use App\Exceptions\RecipientUserNotFound;
use App\Http\Requests\DepositRequest;
use App\Http\Requests\TransferRequest;
use App\Models\Wallet;
use App\Services\TransactionService;
use Illuminate\Support\Facades\Auth;

class WalletController extends Controller
{
    private TransactionService $service;

    public function __construct(TransactionService $service)
    {
        $this->service = $service;
    }

    public function dashboard()
    {
        $wallet = Wallet::firstOrCreate(['user_id' => Auth::id()], ['balance_cents' => 0]);
        return view('dashboard', compact('wallet'));
    }

    public function showDepositForm()
    {
        return view('deposit');
    }

    public function deposit(DepositRequest $request)
    {   
        $inputs = $request->validated();
        try {
            $this->service->deposit(Auth::user(), $inputs['amount'], $inputs['idempotency_key']);
            return redirect()->route('dashboard')->with('success', 'Depósito realizado com sucesso!');
        } catch (InvalidValueException $e) {
            return redirect()->back()->withErrors(['erro' => $e->getMessage()]);
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['erro' => 'Ocorreu um erro ao processar o depósito.']);
        }
    }

    public function showTransferForm()
    {
        return view('transfer');
    }

    public function transfer(TransferRequest $request)
    {
        $inputs = $request->validated();
        try {
            $this->service->transfer(Auth::user(), $inputs['email'], $inputs['amount'], $inputs['idempotency_key']);
        } catch (InsufficientFundsException $e) {
            return back()->withErrors(['erro' => 'Saldo insuficiente.']);
        } catch (RecipientUserNotFound $e) {
            return back()->withErrors(['erro' => $e->getMessage()]);
        } catch (\App\Exceptions\InvalidActionException $e) {
            return back()->withErrors(['erro' => $e->getMessage()]);
        }

        return redirect()->route('dashboard')->with('success', 'Transferência realizada com sucesso!');
    }
}
