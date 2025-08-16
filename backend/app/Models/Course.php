<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    protected $guarded = false;

    public function lessons () {
        return $this->hasMany(Lesson::class, 'course_id');
    }
}
