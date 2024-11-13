<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Estudiante extends Model
{
    use HasFactory;

    protected $guarded = ['id'];
    public function periodo()
    {
        return $this->belongsTo(Periodo::class);
    }
    public function escuela()
    {
        return $this->belongsTo(Escuela::class);
    }
    public function estudianteActividades()
    {
        return $this->hasMany(EstudianteActividad::class, 'estudiante_id')->with('actividad');
    }
}
