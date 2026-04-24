<?php

namespace App\Models;

use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    //
    use HasFactory;
    protected $table= 'sections';

    protected $fillable= [
        'grade_id',
        'name',
        'is_active',
    ];
    protected function casts():array
    {
        return[

            'is_active'=>'boolean',
        ];
    }

    public function students()
    {
        return $this->hasMany(Student::class);

    }
    // other relationships and method...?

    public function grade()
    {
        return $this->belongsTo(Grade::class); // a section belogs to a grade
    }
    // a section has many subjects through teacher assignments
    public function subjects()
    {
        return $this->hasMany(Subject::class);
    }

    // a teacher has many teache assignment
    public function teacherAssignments()
    {
        return $this->hasMany(TeacherAssignment::class);
    }
    // get full section name
    // Grade 10-section A

    public function getFullNameAttribute()
    {
        return $this->grade->name . ' - Section ' .$this->name;
    }
}
