<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class item extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'id',
        'marca',
        'tipo',
        'talla',
        'stock'
    ];
    
    /**
     * Un Item tiene muchos registros (registries).
     */
    public function registries()
    {
        return $this->hasMany(Registry::class);
    }

}
