<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventParticipant extends Model
{
    use HasFactory;
    protected $table = 'event_participants';
    protected $fillable = [
        'event_id',
        'participant_id',
        'qr',
        'intolerance',
        'breakfast',
        'food_1',
        'food_2',
        'day_1',
        'day_2'
    ];
}
