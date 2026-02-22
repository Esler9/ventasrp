<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankPosTerminal extends Model
{
    protected $fillable = [
        'bank_account_id',
        'name',
        'commission_percent',
        'is_active',
    ];

    protected $casts = [
        'commission_percent' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }
}
