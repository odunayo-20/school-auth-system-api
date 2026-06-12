<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    protected $fillable = ['name', 'code', 'faculty_id'];

    protected static function booted(): void
    {
        static::creating(function (Department $department) {
            if (empty($department->code)) {
                $prefix = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $department->name), 0, 3));
                if (strlen($prefix) < 3) {
                    $prefix = str_pad($prefix, 3, 'X');
                }
                $count = 1;
                $code = $prefix . sprintf('%02d', $count);
                while (static::where('code', $code)->exists()) {
                    $count++;
                    $code = $prefix . sprintf('%02d', $count);
                }
                $department->code = $code;
            }
        });
    }

    public function faculty()
    {
        return $this->belongsTo(Faculty::class);
    }


}
