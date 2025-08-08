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
}
