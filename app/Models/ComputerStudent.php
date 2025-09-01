<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComputerStudent extends Model
{
    protected $table = 'computer_students';

    protected $fillable = [
        'student_id',
        'computer_id',
    ];


    public function computer(): BelongsTo
    {
        return $this->belongsTo(Computer::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
