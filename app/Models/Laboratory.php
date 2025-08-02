<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Laboratory extends Model
{
    protected $fillable = [
        "name",
        "code",
        "description",
        "status"
    ];

    public function computers(){
        return $this->hasMany(Computer::class);
    }
}
