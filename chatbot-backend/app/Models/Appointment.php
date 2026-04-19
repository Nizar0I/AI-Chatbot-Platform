<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    protected $fillable = [
        'conversation_id',
        'patient_name',
        'phone',
        'service',
        'date',
        'time',
        'status',
        'notes',
    ];
}
