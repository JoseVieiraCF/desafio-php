<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Wallet;

class WalletTest extends TestCase
{
    use RefreshDatabase;

    public function test_deposit_increases_balance(): void
    {
        $user = User::factory()->create(['password' => bcrypt('password')]);

        $this->actingAs($user)->post('/deposit', [
            'amount' => '10.00',
            'idempotency_key' => 'test-dep-1',
        ])->assertRedirect('/dashboard');

        $wallet = Wallet::where('user_id', $user->id)->first();
        $this->assertNotNull($wallet);
        $this->assertEquals(1000, $wallet->balance_cents);
    }

    public function test_transfer_moves_funds_between_users(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();

        $this->actingAs($alice)->post('/deposit', ['amount' => '50.00', 'idempotency_key' => 'seed-1']);

        $this->actingAs($alice)->post('/transfer', ['email' => $bob->email, 'amount' => '25.00', 'idempotency_key' => 'tx-1'])
            ->assertRedirect('/dashboard');

        $aliceWallet = Wallet::where('user_id', $alice->id)->first();
        $bobWallet = Wallet::where('user_id', $bob->id)->first();

        $this->assertEquals(2500, $aliceWallet->balance_cents);
        $this->assertEquals(2500, $bobWallet->balance_cents);
    }
}
