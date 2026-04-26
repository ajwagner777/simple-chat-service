<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatRoom extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'is_private',
        'password',
        'owner_id',
    ];

    protected $hidden = ['password'];

    protected function casts(): array
    {
        return [
            'is_private' => 'boolean',
        ];
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'chat_room_user')->withTimestamps();
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
}
