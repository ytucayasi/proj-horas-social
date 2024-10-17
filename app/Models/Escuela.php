<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Escuela extends Model
{
    use HasFactory;
    protected $table = 'escuelas';
    protected $guarded = ['id'];
    public function roles()
    {
        return $this->hasMany(Role::class, 'escuela_id', 'id');
    }
}
