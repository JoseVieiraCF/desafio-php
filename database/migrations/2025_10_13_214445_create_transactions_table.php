<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionsTable extends Migration
{
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->enum('type', ['deposit', 'transfer', 'reversal']);
            $table->bigInteger('amount_cents');
            $table->unsignedBigInteger('from_user_id')->nullable();
            $table->unsignedBigInteger('to_user_id')->nullable();
            $table->enum('status', ['pending','completed','failed','reversed'])->default('pending');
            $table->json('metadata')->nullable();
            $table->unsignedBigInteger('reversed_transaction_id')->nullable();
            $table->string('idempotency_key')->nullable();
            $table->timestamps();

            $table->index(['from_user_id']);
            $table->index(['to_user_id']);
            $table->unique('idempotency_key');
            $table->foreign('reversed_transaction_id')->references('id')->on('transactions')->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::dropIfExists('transactions');
    }
}
