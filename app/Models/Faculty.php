<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Faculty extends Model
{
    protected $fillable = [
        'name',
        'school_id',
    ];

    public function students()
    {
        return $this->hasMany(Student::class);
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }
}
