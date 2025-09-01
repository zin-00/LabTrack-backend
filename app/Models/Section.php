<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    protected $fillable = [
        'name',
        'description'
    ];

    public function students(){
        return $this->hasMany(Student::class);
    }
}
