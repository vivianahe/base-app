<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailMod extends Model
{
    use HasFactory;
    protected $table = 'email_mod';
    protected $fillable = [
        'email',
        'SMTPAuth',
        'SMTPSecure',
        'host',
        'port',
        'password',
        'from',
        'api',
        'token',
        'view',
        'user_id'
    ];
}
