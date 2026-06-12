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

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (empty($model->code)) {
                $words = explode(' ', strtoupper($model->name));
                $code = '';
                foreach ($words as $word) {
                    $code .= substr($word, 0, 1);
                }
                if (strlen($code) < 3) {
                    $code = strtoupper(substr(str_replace(' ', '', $model->name), 0, 3));
                }
                
                // Ensure it's unique enough or just use the generated code
                $originalCode = $code;
                $counter = 1;
                while (self::where('code', $code)->exists()) {
                    $code = $originalCode . $counter;
                    $counter++;
                }

                $model->code = $code;
            }
        });
    }

    public function students()
    {
        return $this->hasMany(Student::class);
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }
}
