<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    protected $fillable = [
        'student_id',
        'first_name',
        'middle_name',
        'last_name',
        'email',
        'rfid_uid',
        'program_id',
        'year_level_id',
        'section_id',
        'status',
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function computer_logs(): HasMany
    {
        return $this->hasMany(ComputerLog::class);
    }

    public function year_level(): BelongsTo
    {
        return $this->belongsTo(YearLevel::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function computers(): BelongsToMany
    {
        return $this->belongsToMany(Computer::class, 'computer_students')
            ->withTimestamps();
    }

    // Add this method to get full name
    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }

    // Add this method to check if student is assigned to any computer
    public function getIsAssignedAttribute()
    {
        return $this->computers()->exists();
    }
}
