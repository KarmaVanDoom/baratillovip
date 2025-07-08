<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Registry extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'id',
        'item_id',
        'fecha_hora_ingreso',
        'color',
        'estado',
        'precio'
    ];

    /**
     * Un registro (Registry) pertenece a un Item.
     */
    public function item()
    {
        return $this->belongsTo(Item::class);
    }

}
