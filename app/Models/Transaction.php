<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $casts = ['metadata' => 'array'];
    protected $fillable = [
        'uuid',
        'type',
        'amount_cents',
        'from_user_id',
        'to_user_id',
        'status',
        'metadata',
        'reversed_transaction_id',
        'idempotency_key'
    ];

    public function fromUser() { return $this->belongsTo(User::class, 'from_user_id'); }
    public function toUser() { return $this->belongsTo(User::class, 'to_user_id'); }
    public function reversedTransaction() { return $this->belongsTo(self::class, 'reversed_transaction_id'); }
}
