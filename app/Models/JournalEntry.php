<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JournalEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'entry_date',
        'account_head',
        'reference_type',
        'reference_id',
        'narration',
        'debit',
        'credit',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class)->withTrashed();
    }
}
