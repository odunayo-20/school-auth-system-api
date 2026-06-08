<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class School extends Model
{
    protected $fillable = [
        'name',
        'code',
    ];

    public function students()
    {
        return $this->hasMany(Student::class);
    }

    public function faculties()
    {
        return $this->hasMany(Faculty::class);
    }
    public function departments()
    {
        return $this->hasMany(Department::class);
    }

}
