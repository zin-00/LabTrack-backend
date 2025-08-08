<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    protected $fillable = [
        'student_id',
        'first_name',
        'middle_name',
        'last_name',
        'email',
        'rfid_uid',
        'program_id'
    ];


    public function program(){
        return $this->belongsTo(Program::class);
    }
    public function computer_logs(){
        return $this->hasMany(ComputerLog::class);
    }
}
