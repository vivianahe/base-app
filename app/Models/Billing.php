<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Billing extends Model
{
    use HasFactory;
    protected $table = 'billing';
    protected $fillable = [
        'company_name',
        'cif_nif',
        'billing_email',
        'billing_address',
        'billing_cp',
        'billing_locality',
        'billing_province',
        'billing_country',
        'user_id',
        'participant_id'
    ];
}
