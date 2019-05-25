<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\User;
use App\Video;
use App\Imagen;
use App\Log;
use App\Post;
use App\Comentario;
use Illuminate\Support\Facades\DB;
use App\Helpers\JwtAuth;

class PostController extends Controller
{
     public function index(Request $request) {
                $posts = Post::where('status','ACEPTADO')->get()->load('user')->load('comments');
            
                return response()->json(array(
                            'posts' =>  $posts,
                            'status' => 'success'
                                ), 200);
            }
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
                $post->title = $params->title;
                $post->description = $params->description;
                $post->status = "ACTIVAR";
                
              
                $array_contenido=[
                    'log' => 'NUEVA POST '.$post->id,
                    'titulo' => $post->title,
                    'contenido' =>$post->description,
                    'prioridad' => 1,
                    'usuario' => $post->user_id
                ];

                $post->save();
                $log= new Log();
                $log->prioridad=1;
                $log->nombre='NUEVO POST '.$post->id;
                $log->save();

                //para descargar el archivo json con formato de contenido-id del mensaje
                $json_string = json_encode($array_contenido);
                $file =  "C:/wamp64/www/ApiSpinningCH/logs/NUEVO POST ".$post->id .'.json';
               // file_put_contents($file, $json_string);
    
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
    
      //hay que mejorar la validacion
      public function update($id, Request $request) {
        $hash = $request->header('Authorization', null);

        $jwtAuth = new JwtAuth();
        $checktoken = $jwtAuth->checkToken($hash);

        if ($checktoken) {
            //recoger los parametros que llegan por post
            $json= $request->input('json',null);
            $params = json_decode($json);
            $params_array=json_decode($json,true);
         
            
            //validar los datos
            $validate = \Validator::make($params_array, [
                        'title' => 'required',
                        'description' => 'required'
            ]);
            if ($validate->fails()) {
                return response()->json($validate->errors(), 400);
            }
       
            //actualizar el coche
            unset($params_array['id']);
            unset($params_array['user_id']);
            unset($params_array['created_at']);
            unset($params_array['comments']);
            unset($params_array['user']);
          

            $post=Post::where('id',$id)->update($params_array);
           

            $array_contenido=[
                'log' => 'POST EDITADO '.$id,
                'parametros'=> $params_array,
                'prioridad' => 2,
                'usuario' => $id
            ];

            $log= new Log();
            $log->prioridad=2;
            $log->nombre='POST EDITADO '.$id;
            $log->save();

            //para descargar el archivo json con formato de contenido-id del mensaje
            $json_string = json_encode($array_contenido);
            $file =  "C:/wamp64/www/ApiSpinningCH/logs/POST EDITADO ".$id .'.json';
            //file_put_contents($file, $json_string);
            
            $data = array(
                'post' => $params,
                'message' => 'El usuario se ha actualizado correctamente',
                'status' => 'success',
                'code' => 200
            );
            
            
        } else {
            $data = array(
                'message' => 'Login incorecto al update',
                'status' => 'error',
                'code' => 300
            );
        }
        return response()->json($data, 200);
    }


        public function destroy($id, Request $request){
            $hash = $request->header('Authorization', null);
            
            $jwtAuth= new JwtAuth();
            $checkToken=$jwtAuth->checkToken($hash);
            
            if($checkToken){
                //comprobar si existe el registro
                $post=Post::find($id);
                
                $vercomentarios=Comentario::where('post_id',$id)->get();
              
                $array_comentarios=[];
             
                foreach($vercomentarios as $ver){
                    $array_comentario=[
                        'comentario' =>$ver->comentario ,
                        'id'=> $ver->id,
                        'usuario'=> $ver->user_id
                    ];
                    array_push($array_comentarios, $array_comentario);
                }
              
                $array_contenido=[
                    'log' => 'POST ELIMINADO '.$post->id,
                    'titulo' => $post->title,
                    'contenido' =>$post->description,
                    'prioridad' => 3,
                    'comentarios' => $array_comentarios,
                    'usuario' => $post->user_id
                ];
    
                $log= new Log();
                $log->prioridad=3;
                $log->nombre='POST ELIMINADO '.$post->id;
                $log->save();
    
                //para descargar el archivo json con formato de contenido-id del mensaje
                $json_string = json_encode($array_contenido);
                $file =  "C:/wamp64/www/ApiSpinningCH/logs/POST ELIMINADO ".$post->id .'.json';
                file_put_contents($file, $json_string);
    

                //borrar los comentarios primero
                $comentarios=Comentario::where('post_id',$id)->get()->each->delete();
            
                $post->delete();
                
                //devolver el registro borrado
                 $data = array(
                    'post' => $post,
                    'message' => 'Comentario borrado correctamente',
                    'status' => 'error',
                    'code' => 200
                );
                 
            }else{
                  $data = array(
                    'message' => 'Login incorecto',
                    'status' => 'error',
                    'code' => 400
                );
            }     
            
            return response()->json($data, 200);
        }

        public function show($id) {
            $post = Post::find($id)->load('user')->load('comments');
            if(is_object($post)){
                $post = Post::find($id)->load('user')->load('comments');
                $comentarios = Comentario::where('post_id',$id)->where('status','ACTIVADO')->get()->load('usercomentario');
    
                return response()->json(array(
                    'post' => $post,
                    'comentarios' => $comentarios,
                    'status' => 'success'
                        ), 200);
            }
            else{
                return response()->json(array(
                    'message' => 'El post no existe',
                    'status' => 'error'
                        ), 400);
            }
           
    
          
        }
    
}
