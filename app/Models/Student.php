<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    //
    use HasFactory;
    protected $table = 'students';
    protected $fillable = [
        'user_id',
        'guardian_id',
        'grade_id',
        'section_id',
        'addmission_number',
        'date_of_birth',
        'gender',
        'is_active',
    ];

    protected function casts():array
    {
        return [
            'guardian_id'=>'integer',
            'grade_id'=>'integer',
            'section_id'=>'integer',
            'addmission_number'=>'string',
            'date_of_birth'=>'date',
            'gender'=>'string',
            'is_active'=>'boolean',

        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function guardian()
    {
        return $this->belongsTo(Guardian::class);
    }

    public function grade()
    {
        return $this->belongsTo(Grade::class);
    }
    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    // subjects was here just i comment it..
    //


    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }


    // helper to get student's full name,email,,

    public function getNameAttribute()
    {
        return $this->user->name;
    }
     // get students email

     public function getEmailAttribute()
     {
        return $this->user->email;
     }
     public function getAttendancePercentageAttribute()
     {
        $total = $this->attendance()->count();
        if ($total === 0) return 0;
        $present = $ $this->attendance()->whereIn('status', ['present', 'absent', 'late',])->count();
        return round(($present / $total) * 100, 3);
     }
}
