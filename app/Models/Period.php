<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory; 

class Period extends Model  
{
    use HasFactory;  

    protected $table = 'periods';

    protected $fillable = [ 
        'name',              
        'period_number',
        'start_time',
        'end_time',
        'is_break',          
        'break_name',        
        'is_active',        
    ];

    protected function casts(): array
    {
        return [
            'period_number' => 'integer',
            'start_time' => 'datetime:H:i',  
            'end_time' => 'datetime:H:i',   
            'is_break' => 'boolean',         
            'is_active' => 'boolean',        
        ];
    }

    /**
     * A period has many attendance records
     */
    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'period_id');
    }

    /**
     * A period has many schedule entries
     */
    public function schedules()
    {
        return $this->hasMany(Schedule::class, 'period_id');
    }

    /**
     * Scope to get only class periods (not breaks)
     */
    public function scopeClasses($query)
    {
        return $query->where('is_break', false);
    }

    /**
     * Scope to get only breaks
     */
    public function scopeBreaks($query)
    {
        return $query->where('is_break', true);
    }

    /**
     * Scope to order by period number
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('period_number');
    }

    /**
     * Scope to get only active periods
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get formatted time range (e.g., "08:00 - 08:45")
     */
    public function getTimeRangeAttribute(): string
    {
        return $this->start_time->format('H:i') . ' - ' . $this->end_time->format('H:i');
    }

    /**
     * Get display name with time (e.g., "Period 1 (08:00 - 08:45)")
     */
    public function getDisplayNameAttribute(): string
    {
        if ($this->is_break) {
            return $this->break_name . ' (' . $this->time_range . ')';
        }
        return $this->name . ' (' . $this->time_range . ')';
    }

    /**
     * Get duration in minutes
     */
    public function getDurationAttribute(): int
    {
        $start = \Carbon\Carbon::parse($this->start_time);
        $end = \Carbon\Carbon::parse($this->end_time);
        return $start->diffInMinutes($end);
    }
}