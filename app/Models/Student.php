<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Student extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'phone',
        'department_id',
        'faculty_id',
        'school_id',
        'matric_number',
        'status',
        'confirmed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'matric_number' => 'string',
            'confirmed_at' => 'datetime',
        ];
    }

    /**
     * Get the user that owns the student record.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the department the student belongs to.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the faculty the student belongs to.
     */
    public function faculty(): BelongsTo
    {
        return $this->belongsTo(Faculty::class);
    }

    /**
     * Get the school the student belongs to.
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
