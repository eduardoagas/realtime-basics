<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Character extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'maxhp',
        'hp',
        'level',
        'pattack',
        'mattack',
        'defense',
        'agility',
        'stamina'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
