<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Comentario extends Model
{
    protected $table="comentarios";

     //relacion de muchos a uno
     public function usercomentario(){
        return $this->belongsTo('App\User','user_id');
    }

     //relacion de muchos a uno
     public function imagen_comentario(){
        return $this->belongsTo('App\Imagen','imagen_id');
    }
}
