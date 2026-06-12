<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Faculty extends Model
{
    protected $fillable = [
        'name',
        'code',
        'school_id',
    ];

    // protected static function booted(): void
    // {
    //     static::creating(function (Faculty $faculty) {
    //         if (empty($faculty->code)) {
    //             $prefix = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $faculty->name), 0, 3));
    //             if (strlen($prefix) < 3) {
    //                 $prefix = str_pad($prefix, 3, 'X');
    //             }
    //             $count = 1;
    //             $code = $prefix . sprintf('%02d', $count);
    //             while (static::where('code', $code)->exists()) {
    //                 $count++;
    //                 $code = $prefix . sprintf('%02d', $count);
    //             }
    //             $faculty->code = $code;
    //         }
    //     });
    // }



    public function students()
    {
        return $this->hasMany(Student::class);
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }
}
