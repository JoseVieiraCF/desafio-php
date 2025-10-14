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
            [$fromWallet, $toWallet] = $this->lockWalletsDeterministically($from->id, $toUser->id);
            
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
            $originalTx = Transaction::where('id', $transactionId)
                ->lockForUpdate()
                ->firstOrFail();

            $this->ensureNotAlreadyReversed($originalTx);
            [$fromUserId, $toUserId, $amount] = $this->extractReversalDetails($originalTx);
            [$fromWallet, $toWallet] = $this->lockWalletsDeterministically($fromUserId, $toUserId);
            $this->processReversalBalances($fromWallet, $toWallet, $amount);
            $reversal = $this->createReversalTransaction($originalTx, $actor, $amount, $fromUserId, $toUserId);
            $this->markTransactionAsReversed($originalTx);
            return $reversal;
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

    private function lockWalletsDeterministically(?int $fromUserId, ?int $toUserId): array
    {
        $ids = array_filter([$fromUserId, $toUserId]);
        sort($ids);

        foreach ($ids as $id) {
            $this->getOrCreateWalletLocked($id);
        }

        $fromWallet = $fromUserId ? Wallet::where('user_id', $fromUserId)->lockForUpdate()->first() : null;
        $toWallet = $toUserId ? Wallet::where('user_id', $toUserId)->lockForUpdate()->first() : null;

        return [$fromWallet, $toWallet];
    }

    private function extractReversalDetails(Transaction $tx): array
    {
        $fromUserId = $tx->to_user_id;
        $toUserId = $tx->from_user_id;
        $amount = $tx->amount_cents;

        if ($fromUserId === $toUserId) {
            $toUserId = null;
        }

        return [$fromUserId, $toUserId, $amount];
}

    private function ensureNotAlreadyReversed(Transaction $tx): void
    {
        if ($tx->status === 'reversed') {
            throw new AlreadyReversedException('Transação já foi estornada.');
        }
    }

    private function processReversalBalances(?Wallet $fromWallet, ?Wallet $toWallet, int $amount): void
    {
        if ($fromWallet) {
            if ($fromWallet->balance_cents < $amount) {
                throw new InsufficientFundsException('Saldo insuficiente para estornar a transação!');
            }

            $this->updateBalance($fromWallet, -$amount);
        }

        if ($toWallet) {
            $this->updateBalance($toWallet, $amount);
        }
    }

    private function createReversalTransaction(
        Transaction $originalTx,
        User $actor,
        int $amount,
        ?int $fromUserId,
        ?int $toUserId
    ): Transaction {
        return Transaction::create([
            'uuid' => Str::uuid(),
            'type' => 'reversal',
            'amount_cents' => $amount,
            'from_user_id' => $fromUserId,
            'to_user_id' => $toUserId,
            'status' => 'completed',
            'reversed_transaction_id' => $originalTx->id,
            'metadata' => ['reversed_by' => $actor->id],
        ]);
    }

    private function markTransactionAsReversed(Transaction $tx): void
    {
        $tx->update(['status' => 'reversed']);
    }
}
