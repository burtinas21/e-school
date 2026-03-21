<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'role_id',
        'is_active',

    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'phone'=>'string',
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active'=>'boolean',
        ];   
    }
    //  a user may have one role
    public function role()
    {
        return $this->belongsTo(Role::class);
    }
    
    // a user may have one student profile
    public function student()
    {
        return $this->hasOne(Student::class);
    }

    // a  user may have one teacher profile

    public function teacher()
    {
        return $this->hasOne(Teacher::class);
    }
    // check if a user have one parent profile
    public function guardian()
    {
        return $this->hasOne(Guardian::class);
    }

    // check if a user is admin
    public function isAdmin()
    {
        return $this->role_id === 1;
    }
// check if i user is teacher
    public function isTeacher():bool
    {
      return $this->role_id === 2;
    }
    // check if a user is student
    public function isStudent()
    {
        return $this->role_id===3;
    }
    // check if a user is parent
    public function isGuardian()
    {
        return $this->role_id === 4;
    }
    
}
