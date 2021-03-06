<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\User;
use App\Video;
use App\Log;
use App\Imagen;
use App\Post;
use App\Comentario;
use Illuminate\Support\Facades\DB;
use App\Helpers\JwtAuth;

class ComentarioController extends Controller
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
            $comentario = new Comentario();
            $comentario->user_id = $user->sub;
         
            $comentario->comentario = $params->comentario;
            $comentario->status = "ACTIVAR";
            if($params->imagen_id != NULL){

                $comentario->imagen_id = $params->imagen_id;
                $comentario->video_id = NULL;
                $comentario->post_id = NULL;
            
            }
         if($params->video_id != NULL){
                $comentario->imagen_id = NULL;
                $comentario->video_id = $params->video_id;
                
                $comentario->post_id = NULL;
            }
        if($params->post_id != NULL){
                
                $comentario->imagen_id = NULL;
                $comentario->video_id = NULL;
                $comentario->post_id = $params->post_id;
            }
          
           
            $comentario->save();


            $array_contenido=[
                'log' => 'NUEVO COMENTARIO '.$comentario->id,
                'prioridad' => 1,
                'comentario'=> $comentario->comentario,
                'usuario' => $comentario->user_id
            ];


            $log= new Log();
            $log->prioridad=1;
            $log->nombre='NUEVO COMENTARIO '.$comentario->id;
            $log->save();

            //para descargar el archivo json con formato de contenido-id del mensaje
            $json_string = json_encode($array_contenido);
            $file =  "C:/wamp64/www/ApiSpinningCH/logs/NUEVO COMENTARIO ".$comentario->id .'.json';
           // file_put_contents($file, $json_string);

          

            $data = array(
                'comentario' => $comentario,
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


    public function destroy($id, Request $request){
        $hash = $request->header('Authorization', null);
        
        $jwtAuth= new JwtAuth();
        $checkToken=$jwtAuth->checkToken($hash);
        
        if($checkToken){
            //comprobar si existe el registro
            $comentario=Comentario::find($id);

            $array_contenido=[
                'log' => 'COMENTARIO ELIMINADO '.$id,
                'prioridad' => 3,
                'comentario'=>$comentario->comentario,
                'usuario_del_com'=>$comentario->user_id
            ];

            $log= new Log();
            $log->prioridad=3;
            $log->nombre='COMENTARIO ELIMINADO '.$comentario->id;
            $log->save();

            //para descargar el archivo json con formato de contenido-id del mensaje
            $json_string = json_encode($array_contenido);
            $file =  "C:/wamp64/www/ApiSpinningCH/logs/COMENTARIO ELIMINADO ".$id .'.json';
          //  file_put_contents($file, $json_string);

          

            //borrarlo
            $comentario->delete();
            
            //devolver el registro borrado
             $data = array(
                'comentario' => $comentario,
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
                        'comentario' => 'required'
                        
            ]);
            if ($validate->fails()) {
                return response()->json($validate->errors(), 400);
            }
            
            //actualizar el coche
            unset($params_array['id']);
            unset($params_array['usercomentario']);
            unset($params_array['imagen_comentario']);
            unset($params_array['video_id']);
            unset($params_array['post_id']);
            unset($params_array['created_at']);
          
            $comentario=Comentario::where('id',$id)->update($params_array);

            $array_contenido=[
                'log' => 'COMENTARIO EDITADO '.$id,
                'prioridad' => 2,
                'parametros'=>$params_array
               
            ];


            $log= new Log();
            $log->prioridad=2;
            $log->nombre='COMENTARIO EDITADO '.$id;
            $log->save();

            //para descargar el archivo json con formato de contenido-id del mensaje
            $json_string = json_encode($array_contenido);
            $file =  "C:/wamp64/www/ApiSpinningCH/logs/COMENTARIO EDITADO ".$id .'.json';
          //  file_put_contents($file, $json_string);


            $data = array(
                'comentario' => $params,
                'message' => 'El comentario se ha actualizado correctamente',
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

    public function show($id) {
        $comentario = Comentario::find($id)->load('usercomentario')->load('imagen_comentario');
        if(is_object($comentario)){
            $comentario = Comentario::find($id)->load('usercomentario')->load('imagen_comentario');
           
            return response()->json(array(
                'comentario' => $comentario,
                'status' => 'success'
                    ), 200);
        }
        else{
            return response()->json(array(
                'message' => 'El comentario no existe',
                'status' => 'error'
                    ), 400);
        }
       

      
    }
}
