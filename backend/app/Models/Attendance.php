<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    //
    use HasFactory;
    protected $table = 'attendances';
    protected $fillable = [
        'grade_id',
        'section_id',
        'subject_id',
        'student_id',
        'teacher_id',
        'period_id',
        'date',
        'status',
        'remarks',
    ];

    protected function casts():array
    {
        return [
            'grade_id'=>'integer',
            'section_id'=>'integer',
            'subject_id'=>'integer',
            'student_id'=>'integer',
            'teacher_id'=>'integer',
            'period_id'=>'integer',
            'date'=>'date',
            'status'=>'string',
        ];

    }
// an attendance record belogs to a section
    public function  grade()
    {
        return $this->belongsTo(Grade::class);
    }
// an attendance record belogs to one section
    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    // an attendane record belogs to one subject
    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }
    // an attendance record belogs to to a student

    public function  student()
    {
        return $this->belongsTo(Student::class);
    }
    // get an attendance record belogs to a teacher
    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }
    // an attendance record belogs to a period
    public function period()
    {
        return $this->belongsTo(Period::class, 'period_id');

    }

    // helpers to get status badge color
    public function getStatusColorAttribute()
    {
        return match($this->status)
        {
            'present'=>'green',
            'absent'=>'red',
            'late'=>'yellow',
            'permission'=>'blue',
            default =>'gray',
        };
    }

    // helpers to get formatted date

    public function getFormattedDateAttribute()
    {
        return $this->date->format('Y,m,d');

    }
}
