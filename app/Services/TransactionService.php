<?php

namespace App\Services;

use App\Exceptions\AlreadyReversedException;
use App\Exceptions\InsufficientFundsException;
use App\Exceptions\InvalidActionException;
use App\Exceptions\InvalidValueException;
use App\Exceptions\RecipientUserNotFound;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TransactionService
{
    public function deposit(User $user, int $amount, ?string $idempotencyKey = null): Transaction
    {
        $amountCents = $this->toCents($amount);
        if ($amountCents <= 0) throw new InvalidValueException('O Valor de depósito deve ser maior que zero!');
        
        return DB::transaction(function () use ($user, $amountCents, $idempotencyKey) {
             if ($idempotencyKey) {
                if ($existing = $this->getExistingTransaction($idempotencyKey)) {
                    return $existing;
                }
            }
            $wallet = $this->getOrCreateWalletLocked($user->id);
            $this->updateBalance($wallet, $amountCents);

            $tx = Transaction::create([
                'uuid' => Str::uuid(),
                'type' => 'deposit',
                'amount_cents' => $amountCents,
                'from_user_id' => $user->id,
                'to_user_id' => $user->id,
                'status' => 'completed',
                'idempotency_key' => $idempotencyKey,
            ]);

            return $tx;
        });
    }

    public function transfer(User $from, string $toUserEmail, int $amount, ?string $idempotencyKey = null): Transaction
    {
        $amountCents = $this->toCents($amount);
        $toUser = User::where('email', $toUserEmail)->first();

        if (!$toUser) throw new RecipientUserNotFound();
        if ($from->id === $toUser->id) throw new InvalidActionException('Não é possível transferir para a mesma conta!');
        if ($amountCents <= 0) throw new InvalidValueException('O valor de transferência deve ser maior que zero!');

        return DB::transaction(function () use ($from, $toUser, $amountCents, $idempotencyKey) {
            if ($idempotencyKey) {
                if ($existing = $this->getExistingTransaction($idempotencyKey)) return $existing;
            }
            [$fromWallet, $toWallet] = $this->lockWalletsDeterministically($from, $toUser);
            
            if ($fromWallet->balance_cents < $amountCents) {
                throw new InsufficientFundsException('Saldo insuficiente para transferência!');
            }
            
            $this->updateBalance($fromWallet, -$amountCents);
            $this->updateBalance($toWallet, $amountCents);

            $tx = Transaction::create([
                'uuid' => Str::uuid(),
                'type' => 'transfer',
                'amount_cents' => $amountCents,
                'from_user_id' => $from->id,
                'to_user_id' => $toUser->id,
                'status' => 'completed',
                'idempotency_key' => $idempotencyKey,
            ]);

            return $tx;
        });
    }

    public function reverseTransaction(int $transactionId, User $actor): Transaction
    {
        return DB::transaction(function () use ($transactionId, $actor) {
            $tx = Transaction::where('id', $transactionId)->lockForUpdate()->firstOrFail();
            if ($tx->status === 'reversed') throw new AlreadyReversedException();

            $fromUserId = $tx->to_user_id;
            $toUserId = $tx->from_user_id;
            $amount = $tx->amount_cents;

            if ($fromUserId === $toUserId) $toUserId = null;

            
            $ids = [$fromUserId, $toUserId];
            sort($ids);
            $this->getOrCreateWalletLocked($ids[0]);
            if(isset($ids[1])) $this->getOrCreateWalletLocked($ids[1]);

            $fromWallet = Wallet::where('user_id', $fromUserId)->first();
            $toWallet = Wallet::where('user_id', $toUserId)->first();

            if ($fromWallet) {
                if($fromWallet->balance_cents < $amount) {
                    throw new InsufficientFundsException('Saldo insufieciente para estornar a transação!');
                }
                $this->updateBalance($fromWallet, -$amount);
            }
            if ($toWallet) {
                $this->updateBalance($toWallet, $amount);
            }

            $rev = Transaction::create([
                'uuid' => Str::uuid(),
                'type' => 'reversal',
                'amount_cents' => $amount,
                'from_user_id' => $fromUserId,
                'to_user_id' => $toUserId,
                'status' => 'completed',
                'reversed_transaction_id' => $tx->id,
                'metadata' => ['reversed_by' => $actor->id],
            ]);

            $tx->status = 'reversed';
            $tx->save();

            return $rev;
        });
    }

    private function getOrCreateWalletLocked(int $userId): Wallet
    {
        $wallet = Wallet::where('user_id', $userId)->lockForUpdate()->first();

        return $wallet ?? Wallet::create(['user_id' => $userId, 'balance_cents' => 0]);
    }

    private function updateBalance(Wallet $wallet, int $value): void
    {
        $wallet->balance_cents += $value;
        $wallet->save();
    }

    private function toCents(float|int $amount): int
    {
        return (int) round($amount * 100);
    }

    private function getExistingTransaction(?string $idempotencyKey): ?Transaction
    {
        return $idempotencyKey
            ? Transaction::where('idempotency_key', $idempotencyKey)->first()
            : null;
    }

    private function lockWalletsDeterministically(User $from, User $toUser): array {
        [$firstId, $secondId] = [$from->id, $toUser->id];
        if ($firstId > $secondId) {
            [$firstId, $secondId] = [$secondId, $firstId];
        }
        $w1 = Wallet::where('user_id', $firstId)->lockForUpdate()->first() ?? Wallet::create(['user_id' => $firstId, 'balance_cents' => 0]);
        $w2 = Wallet::where('user_id', $secondId)->lockForUpdate()->first() ?? Wallet::create(['user_id' => $secondId, 'balance_cents' => 0]);

        $fromWallet = $from->id === $w1->user_id ? $w1 : $w2;
        $toWallet = $toUser->id === $w1->user_id ? $w1 : $w2;
        return [$fromWallet, $toWallet];
    }
}
