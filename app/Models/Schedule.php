<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{   use HasFactory;
    //
    protected $table ='schedules';
    protected $fillable = [
        'grade_id',
        'section_id',
        'subject_id',
        'teacher_id',
        'period_id',
        'day_of_week',
        'is_active',
    ];

    protected function casts():array
    {
        return [
            'grade_id'=>'integer',
            'section_id'=>'integer',
            'subject_id'=>'integer',
            'teacher_id'=>'integer',
            'period_id'=>'integer',
            'day_of_week'=>'string',
            'is_active'=>'boolean',

        ];
    }

    // relationships
    public function grade()
    {
        return $this->belongsTo(Grade::class);
    }
    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function period()
    {
        return $this->belongsTo(Period::class);
    }

    // helper methods
    public function getTimeRangeAttribute()
    {
        return $this->period->time_range;
    }

    public function getDisplayNameAttribute()
    {
        return $this->subject->name . ' - ' .
                    $this->day_of_week . ' ' .
                    $this->period->display_name;
    }
}
