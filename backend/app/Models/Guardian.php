<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
//  use Illuminate\Notifications\Notification;
use App\Models\Notification; // ✅ Import your custom Notification model

class Guardian extends Model
{
    use HasFactory;

    protected $table = 'guardians';

    protected $fillable = [
        'user_id',
        'occupation',
        'relationship',
        'receive_notifications',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'receive_notifications' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function students()
    {
        return $this->hasMany(Student::class, 'guardian_id');
    }

    // ✅ Corrected: uses your custom Notification model
    public function notifications()
    {
        return $this->hasMany(Notification::class, 'guardian_id');
    }

    public function getNameAttribute()
    {
        return $this->user->name;
    }

    public function getUnreadNotificationsCountAttribute()
    {
        return $this->notifications()->where('is_read', false)->count();
    }
}
