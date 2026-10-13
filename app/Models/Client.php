<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

// app/Models/Client.php
class Client extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'business_name',
        'trade_name',
        'tax_id',
        'tax_regime',
        'industry',
        'contact_name',
        'email',
        'phone',
        'secondary_phone',
        'website',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'status',
        'client_type',
        'credit_limit',
        'payment_terms',
        'notes',
        'metadata',
        'account_manager_id',
        'onboarded_at',
    ];

    protected $casts = [
        'metadata'     => 'array',
        'onboarded_at' => 'datetime',
        'credit_limit' => 'decimal:2',
    ];

    public function projects()
    {
        return $this->hasMany(Project::class, 'user_id');
    }

    public function accountManager()
    {
        return $this->belongsTo(User::class, 'account_manager_id');
    }
}
