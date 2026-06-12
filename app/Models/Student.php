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
        'dob',
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

    protected static function booted(): void
    {
        static::creating(function (Student $student) {
            if (empty($student->matric_number)) {
                $student->matric_number = static::generateMatricNumber($student);
            }
        });
    }

    public static function generateMatricNumber(Student $student): string
    {
        $schoolCode = \App\Models\School::find($student->school_id)?->code ?? 'SCH';
        $facultyCode = \App\Models\Faculty::find($student->faculty_id)?->code ?? 'FAC';
        $departmentCode = \App\Models\Department::find($student->department_id)?->code ?? 'DEP';

        $prefix = $schoolCode . $facultyCode . $departmentCode;

        $lastStudent = static::where('department_id', $student->department_id)
            ->where('matric_number', 'like', $prefix . '%')
            ->orderBy('id', 'desc')
            ->first();

        $sequence = 1;
        if ($lastStudent && preg_match('/(\d+)$/', $lastStudent->matric_number, $matches)) {
            $sequence = intval($matches[1]) + 1;
        }

        return sprintf('%s%04d', $prefix, $sequence);
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
