<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\User;
use App\Video;
use App\Imagen;
use App\Post;
use App\Comentario;
use Illuminate\Support\Facades\DB;
use App\Helpers\JwtAuth;

class PostController extends Controller
{
        public function store(Request $request) {
            $hash = $request->header('Authorization', null);
    
            $jwtAuth = new JwtAuth();
            $checktoken = $jwtAuth->checkToken($hash);
    
            if ($checktoken) {
            //recoger datos por post
                $json = $request->input('json', null);
                $params = json_decode($json);
              
                //conseguir usuario
                $user = $jwtAuth->checkToken($hash, true);
    
                //validacion de los parametros
                //guardar el coche
                $post = new Post();
                $post->user_id = $user->sub;
             
                $post->description = $params->description;
                $post->status = "ACTIVAR";
                
              
               
                $post->save();
    
    
                $data = array(
                    'post' => $post,
                    'status' => 'success',
                    'code' => 200
                );
               
                
            }else {
                $data = array(
                    'message' => 'Login incorecto',
                    'status' => 'error',
                    'code' => 400
                );
            }
            return response()->json($data, 200);
        }
    
    
        
    
}
