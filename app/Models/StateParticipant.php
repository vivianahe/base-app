<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StateParticipant extends Model
{
    use HasFactory;
    protected $table = 'state_participants';
    protected $fillable = [
        'event_id',
        'participant_id',
        'user_id',
        'state'
    ];

}
