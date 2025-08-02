<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Computer extends Model
{
    protected $fillable = [
        "computer_number",
        "ip_address",
        "laboratory_id",
        "status",
        "state"
    ];

    public function laboratory(){
        return $this->belongsTo(Laboratory::class);
    }
    public function computer_logs(){
        return $this->hasMany(ComputerLog::class);
    }
}
