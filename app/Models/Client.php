<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'cnpj',
        'name',
        'fantasy_name',
        'phone',
        'email',
        'logo',
        'employee_quantity',
        'company_size',
        'industry_segment',
        'structured_hr_department',
        'company_profile',
        'responsible_name',
        'responsible_email',
        'responsible_phone',
        'responsible_whatsapp',
        'mission',
        'values',
        'pdi_program',
        'work_regimes',
        'billing_address',
        'billing_email',
        'billing_responsible',
        'payment_methods',
        'payment_date',
        'contract_type',
        'billing_address_id'
    ];

    protected function casts(): array
    {
        return [
            'employee_quantity' => 'integer',
            'company_size' => 'integer',
            'industry_segment' => 'integer',
            'structured_hr_department' => 'integer',
            'pdi_program' => 'integer',
            'payment_methods' => 'integer',
            'contract_type' => 'integer',
            'payment_date' => 'datetime:Y-m-d',
            'work_regimes' => 'array',
        ];
    }

    public function addresses()
    {
        return $this->hasMany(Address::class);
    }

    public function billingAddress()
    {
        return $this->hasOne(BillingAddress::class);
    }
}
