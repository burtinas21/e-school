<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Teacher extends Model
{
    //
    use HasApiTokens, HasFactory, Notifiable;
    use HasFactory;
    protected $table='teachers';

    protected $fillable = [
               'user_id',
               'qualification',
               'employee_id',
               'hire_date',
               'is_active',

    ];
    protected function casts():array
    {
        return [
            'hire_date'=>'date',
            'is_active'=>'boolean',

        ];
    }
    // a teacher belogs to a user

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function assignments()
    {
        return $this->hasMany(TeacherAssignment::class);
    }
    // get section through assignments
    public function sections()
    {
        return $this->belongsToMany(Section::class, 'teacher_assignments')
                   ->withPivot('subject_id')
                   ->withTimestamps();
    }
    // get subject through assignments

    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'teacher_assignments')
                    ->withPivot('section_id')
                    ->withTimestamps();
    }
    public function attendance()
    {
        return $this->hasMany(Attendance::class);
    }
    // get teachers full name from user
    public function getNameAttribute()
    {
        return $this->user->name;
    }
    // get teachers email from user
    public function getEmailAttribute()
    {
        return $this->user->email;
    }
}
