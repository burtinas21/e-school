<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    //
    use HasFactory;
    protected $table = 'notifications';
    protected $fillable = [
        'student_id',
        'guardian_id',
        'teacher_id',
        'type',
        'title',
        'message',
        'sent_at',
        'read_at',
        'status',
    ];
    protected function casts():array
    {
        return [
            'student_id'=>'integer',
            'guardian_id'=>'integer',
            'teacher_id' =>'integer',
            'type'=>'string',
            'title'=>'string',
            'message'=>'string',
            'sent_at'=>'datetime',
            'read_at'=>'datetime',
            'status'=>'string',
        ];
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function student()
    {
         return $this->belongsTo(Student::class);
    }

    public function guardian()
    {
     return $this->belongsTo(Guardian::class);
    }

    // helpers to ckeck if notifications read

    public function isRead():bool
    {
        return !is_null($this->read_at);

    }
// helpers to mark as read
public function markAsRead(): void
{
    $this->update(['read_at'=> now()]);
}

// get time ago
 public function getTimeAgoAttribute()
 {
    return $this->sent_at->diffForHumans();
 }

}
