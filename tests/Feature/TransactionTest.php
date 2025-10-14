<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Transaction;
use App\Models\Wallet;

class TransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_deposit_idempotency(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/deposit', ['amount' => '10.00', 'idempotency_key' => 'idem-1']);
        $this->actingAs($user)->post('/deposit', ['amount' => '10.00', 'idempotency_key' => 'idem-1']);

        $txs = Transaction::where('from_user_id', $user->id)->where('type', 'deposit')->get();
        $this->assertCount(1, $txs);
        $this->assertEquals(1000, $txs->first()->amount_cents);
    }

    public function test_transfer_insufficient_funds(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();

        $this->actingAs($alice)->post('/transfer', ['email' => $bob->email, 'amount' => '10.00', 'idempotency_key' => 't1'])
            ->assertSessionHasErrors('erro');
    }

    public function test_transfer_to_self_is_invalid(): void
    {
        $alice = User::factory()->create();

        $this->actingAs($alice)->post('/transfer', ['email' => $alice->email, 'amount' => '1.00', 'idempotency_key' => 'self-1'])
            ->assertSessionHasErrors('erro');
    }

    public function test_reversal_and_double_reversal(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();

        $this->actingAs($alice)->post('/deposit', ['amount' => '20.00', 'idempotency_key' => 'seed-2']);

        $this->actingAs($alice)->post('/transfer', ['email' => $bob->email, 'amount' => '5.00', 'idempotency_key' => 'tx-2']);

        $tx = Transaction::where('type', 'transfer')->first();
        $this->assertNotNull($tx);

        $this->actingAs($alice)->post('/transactions/' . $tx->id . '/reverse')
            ->assertRedirect('/dashboard');

        $this->actingAs($alice)->post('/transactions/' . $tx->id . '/reverse')
            ->assertSessionHasErrors();
    }
}
