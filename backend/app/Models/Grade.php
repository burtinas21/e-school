<?php

namespace App\Models;

use App\Models\Section;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Grade extends Model
{
    //
    use HasFactory;
    protected $table = 'grades';
    protected $fillable = [
        'name',
        'level',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'level'=>'integer',
            'is_active'=>'boolean',

        ];
    }
// methods or relationships
    public function students()
    {
        return $this->hasMany(Student::class);
    }
    public function sections()
    {
        return $this->hasMany(Section::class);
    }
    public function subjects()
    {
        return $this->hasMany(Subject::class);

    }


}
