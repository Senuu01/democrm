<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Proposal extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'customer_id',
        'title',
        'description',
        'amount',
        'status',
        'valid_until',
        'terms_conditions'
    ];

    protected $casts = [
        'valid_until' => 'datetime',
        'amount' => 'decimal:2'
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where('title', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
    }

    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }
}
