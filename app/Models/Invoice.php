<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'customer_id',
        'proposal_id',
        'invoice_number',
        'amount',
        'status',
        'due_date',
        'paid_at'
    ];

    protected $casts = [
        'due_date' => 'datetime',
        'paid_at' => 'datetime',
        'amount' => 'decimal:2'
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function proposal()
    {
        return $this->belongsTo(Proposal::class);
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
