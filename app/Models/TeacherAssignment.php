<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TeacherAssignment extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'teacher_assignments';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'teacher_id',
        'subject_id',
        'section_id',
        'academic_year',
        'is_primary',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'academic_year' => 'integer',
            'is_primary' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get the teacher that owns the assignment.
     */
    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    /**
     * Get the subject that is assigned.
     */
    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * Get the section that is assigned.
     */
    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    /**
     * Scope a query to only include assignments for current academic year.
     */
    public function scopeCurrentYear($query)
    {
        return $query->where('academic_year', now()->year);
    }

    /**
     * Scope a query to only include primary teachers.
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    /**
     * Scope a query to only include assignments for a specific teacher.
     */
    public function scopeForTeacher($query, $teacherId)
    {
        return $query->where('teacher_id', $teacherId);
    }

    /**
     * Scope a query to only include assignments for a specific section.
     */
    public function scopeForSection($query, $sectionId)
    {
        return $query->where('section_id', $sectionId);
    }

    /**
     * Scope a query to only include assignments for a specific subject.
     */
    public function scopeForSubject($query, $subjectId)
    {
        return $query->where('subject_id', $subjectId);
    }

    /**
     * Get the full assignment description.
     */
    public function getDescriptionAttribute(): string
    {
        if (!$this->teacher || !$this->teacher->user || !$this->subject || !$this->section || !$this->section->grade) {
            return 'Incomplete assignment';
        }

        return $this->teacher->user->name . ' teaches ' .
               $this->subject->name . ' to ' .
               $this->section->grade->name . ' - Section ' .
               $this->section->name;
    }

    /**
     * Get the grade through section.
     */
    public function getGradeAttribute()
    {
        return $this->section->grade ?? null;
    }

    /**
     * Check if this is a valid assignment for the academic year.
     */
    public function isValidForYear($year = null): bool
    {
        $year = $year ?? now()->year;
        return $this->academic_year == $year;
    }

    /**
     * Get the academic year in display format.
     */
    public function getAcademicYearDisplayAttribute(): string
    {
        return $this->academic_year . '-' . ($this->academic_year + 1);
    }
}
