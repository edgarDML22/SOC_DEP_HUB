<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InstructorDisciplina extends Model
{
    protected $table = 'instructor_disciplina';
    public $timestamps = false;
    protected $fillable = [
        'id_instructor',
        'id_disciplina'
    ];

    public function instructor()
    {
        return $this->belongsTo(Instructor::class, 'id_instructor', 'id_instructor');
    }
    public function disciplina()
    {
        return $this->belongsTo(Disciplina::class, 'id_disciplina', 'id_disciplina');
    }
}