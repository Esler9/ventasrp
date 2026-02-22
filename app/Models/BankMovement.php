<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankMovement extends Model
{
    protected $fillable = [
        'bank_account_id',
        'related_account_id',
        'user_id',
        'movement_date',
        'type',
        'amount',
        'description',
        'reference',
        'transfer_group',
    ];

    protected $casts = [
        'movement_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'bank_account_id');
    }

    public function relatedAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'related_account_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
