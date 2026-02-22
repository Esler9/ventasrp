<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalePayment extends Model
{
    protected $fillable = [
        'sale_id',
        'cash_session_id',
        'user_id',
        'method',
        'bank_account_id',
        'card_pos_terminal_id',
        'reference',
        'apply_surcharge',
        'surcharge_percent',
        'surcharge_amount',
        'base_amount',
        'commission_percent',
        'commission_amount',
        'net_amount',
        'amount',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'apply_surcharge' => 'boolean',
        'surcharge_percent' => 'decimal:2',
        'surcharge_amount' => 'decimal:2',
        'base_amount' => 'decimal:2',
        'commission_percent' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function cashSession(): BelongsTo
    {
        return $this->belongsTo(CashSession::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function cardPosTerminal(): BelongsTo
    {
        return $this->belongsTo(BankPosTerminal::class, 'card_pos_terminal_id');
    }
}
