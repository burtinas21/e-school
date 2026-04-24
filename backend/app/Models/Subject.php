<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    //
    use HasFactory;
    protected $table = 'subjects';
    protected $fillable = [
        'name',
        'grade_id',
        'subject_code',
        'description',
        'credits',
        'is_core',
        'is_active',
    ];
     protected function casts():array
     {
        return [
            'grade_id'=>'integer',
            'subject_code'=>'string',
            'credits'=>'decimal:2',
            'is_core'=>'boolean',
            'is_active'=>'boolean',
            'created_at'=>'datetime',
            'updated_at'=>'datetime',

        ];
     }

     public function grade()
     {
        return $this->belongsTo(Grade::class);
     }

     public function teachers()
     {
        return $this->belongsToMany(Teacher::class,  'teacher_assignments')
                    ->withPivot('section_id')
                    ->withTimestamps();
     }
     // get sections through assignments
     public function sections()
    {
        return $this->belongsToMany(Section::class, 'teacher_assignments')
                    ->withPivot('teacher_id')
                    ->withTimestamps();
    }

     public function attendances()
     {
        return $this->hasMany(Attendance::class);
     }
  // helper method to get full subject name with code

  public function getFullNameAttribute()
  {
    return $this->subject_code . ' - ' . $this->name;
  }
}
