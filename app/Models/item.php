<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    public $timestamps = false;

    protected $fillable = [
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
