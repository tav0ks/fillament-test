<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BillingAddress extends Model
{
    use HasFactory;

    protected $fillable = [
        'district',
        'street',
        'zip_code',
        'city',
        'state',
        'number',
        'complement',
        'client_id',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
