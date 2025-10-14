<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransferRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'email' => 'required|email', //exists:users,email
            'amount' => 'required|numeric|min:0.01',
            'idempotency_key' => 'required|string|max:255'
        ];
    }
}
