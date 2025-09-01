<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Computer extends Model
{
    protected $fillable = [
        "computer_number",
        "ip_address",
        "mac_address",
        "is_lock",
        "is_online",
        "laboratory_id",
        "status",
    ];

    public function laboratory(){
        return $this->belongsTo(Laboratory::class);
    }
    public function computer_logs(){
        return $this->hasMany(ComputerLog::class);
    }

    public function students()
    {
        return $this->belongsToMany(Student::class, 'computer_students')
            ->withTimestamps();
    }
     // Add this method to get assigned students count
    public function getAssignedStudentsCountAttribute()
    {
        return $this->students()->count();
    }

    public function computerStudents()
    {
        return $this->hasMany(ComputerStudent::class);
    }
}
