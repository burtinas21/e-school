<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notification;

class Guardian extends Model
{
    //
    use HasFactory;
    protected $table = 'guardians';

    protected $fillable = [
        'user_id',
        //'email',  from user model
       // 'phone',  from user model
        'occupation',
        'relationship',
        'receive_notifications',
    ];
    protected function casts():array
    {
        return [
            'user_id'=>'integer',
            'receive_notifications'=>'boolean',
            'created_at'=>'datetime',
            'updated_at'=>'datetime',
        ];
    }
    // a guardian belogs to a user
    public function user()
    {
        return $this->belongsTo(User::class);
    }
// a guardian has many students(childrens)
    public function students()
    {
        return $this->hasMany(Student::class, 'guardian_id');

    }

     public function notifications()
    {
        return $this->hasMany(Notification::class,  'guardian_id');
    }
// get guardians full name from the user
    public function getNameAttribute()
    {
        return $this->user->name;
    }

    // get unread notifications

    public function getUnreadNotificationsCountAttribute()
    {
        return $this->notifications()->where('is_read', false)->count();
    }

}
