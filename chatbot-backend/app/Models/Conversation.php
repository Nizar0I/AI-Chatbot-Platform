<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    protected $fillable = ['user_name'];
    protected $casts = [
    'data' => 'array',
];

    public function messages()
    {
        return $this->hasMany(Message::class);
    }
}
