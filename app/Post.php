<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $table="posts";
    
    //relacion de muchos a uno
    public function user(){
        return $this->belongsTo('App\User','user_id');
    }

     //relacion One To Many
   public function comments()
   {
       return $this->hasMany('App\Comentario')->orderBy('id','desc');
   }
   
}
