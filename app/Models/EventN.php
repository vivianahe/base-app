<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventN extends Model
{
    use HasFactory;
    protected $table = 'events_noved';
    protected $fillable = [
        'name',
        'capacity',
        'date',
        'hour',
        'price',
        'type_inscription',
        'state',
        'logo',
        'user_id',
    ];
}
