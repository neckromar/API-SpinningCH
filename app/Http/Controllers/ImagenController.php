<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\User;
use App\Video;
use App\Imagen;
use App\Post;
use App\Log;
use App\Comentario;
use Illuminate\Support\Facades\DB;
use App\Helpers\JwtAuth;

class ImagenController extends Controller
{
    public function index(Request $request) {
        $imagenes = Imagen::where('status','ACEPTADO')->get()->load('user')->load('comments');
       
        return response()->json(array(
                    'imagenes' =>  $imagenes,
                    'status' => 'success'
                        ), 200);
    }

    public function show($id) {
        $imagenes = Imagen::find($id)->load('user')->load('comments');
        if(is_object($imagenes)){
            $imagenes = Imagen::find($id)->load('user')->load('comments');
            $comentarios = Comentario::where('imagen_id',$id)->where('status','ACTIVADO')->get()->load('usercomentario');

            return response()->json(array(
                'imagenes' => $imagenes,
                'comentarios' => $comentarios,
                'status' => 'success'
                    ), 200);
        }
        else{
            return response()->json(array(
                'message' => 'La imagen no existe',
                'status' => 'error'
                    ), 400);
        }
       

      
    }
    
    public function store(Request $request) {
        $hash = $request->header('Authorization', null);

        $jwtAuth = new JwtAuth();
        $checktoken = $jwtAuth->checkToken($hash);

        if ($checktoken) {
        //recoger datos por post
            $json = $request->input('json', null);
            $params = json_decode($json);
            $params_array = json_decode($json, true);
            //conseguir usuario
            $user = $jwtAuth->checkToken($hash, true);

            //validacion de los parametros

            $validate = \Validator::make($params_array, [
                        'description' => 'required'
            ]);
            if ($validate->fails()) {
                return response()->json($validate->errors(), 400);
            }
            //guardar el coche
            $imagen = new Imagen();
            $imagen->user_id = $user->sub;
            $imagen->description = $params->description;
            $imagen->status = "ACTIVAR";
            $imagen->imagen_path = $params->imagen_path;
            if($params->imagen_path == "" || $params->imagen_path == null){
               
                    $data = array(
                        'message' => 'Hay que subir una foto',
                        'status' => 'error',
                        'code' => 400
                    );
            }else{
                $array_contenido=[
                    'log' => 'NUEVA IMAGEN '.$imagen->id,
                    'prioridad' => 1,
                    'foto'=>$imagen->imagen_path,
                    'usuario' => $imagen->user_id
                ];


                $imagen->save();

                $log= new Log();
                $log->prioridad=1;
                $log->nombre='NUEVA IMAGEN '.$imagen->id;
                $log->save();
    
                //para descargar el archivo json con formato de contenido-id del mensaje
                $json_string = json_encode($array_contenido);
                $file =  "C:/wamp64/www/ApiSpinningCH/logs/NUEVA IMAGEN ".$imagen->id .'.json';
              //  file_put_contents($file, $json_string);

                $data = array(
                    'imagen' => $imagen,
                    'status' => 'success',
                    'code' => 200
                );
            }
           

            
        } else {
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
                        'description' => 'required'
            ]);
            if ($validate->fails()) {
                return response()->json($validate->errors(), 400);
            }
       
            //actualizar el coche
            unset($params_array['id']);
            unset($params_array['user_id']);
            unset($params_array['created_at']);
            unset($params_array['imagen_path']);
            unset($params_array['comments']);
            unset($params_array['user']);
          

            $imagen=Imagen::where('id',$id)->update($params_array);
           

            $array_contenido=[
                'log' => 'POST EDITADO '.$id,
                'parametros'=> $params_array,
                'prioridad' => 2,
                'usuario' => $id
            ];

            $log= new Log();
            $log->prioridad=2;
            $log->nombre='IMAGEN EDITADO '.$id;
            $log->save();

            //para descargar el archivo json con formato de contenido-id del mensaje
            $json_string = json_encode($array_contenido);
            $file =  __dir__."/logs/IMAGEN EDITADO ".$id .'.json';
           // file_put_contents($file, $json_string);
            
            $data = array(
                'imagen' => $params,
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

    public function upload(Request $request){

        //re4coger datos de la peticion
        $image=$request->file('file0');

        //validate

            $validate=\Validator::make($request->all(),[
            'file0' => 'required|image|mimes:jpg,jpeg,png,gif'
        ]);


        //guardar imagen
        if( !$image || $validate->fails())
        {
            $data = array(
                'status' => 'error',
                'code' => 400,
                'message' => 'Error al subir el archivo '
            );
       
        }else{
          
            $image_name=time().$image->getClientOriginalName();
            \Storage::disk('imagenes')->put($image_name, \File::get($image));
 
            $data=array(
                'code' => 200,
                'imagen_path' => $image_name,
                'status' => 'success'
            );
        }

      
        return response()->json($data,$data['code']);
    }
    
    public function getImage($filename){

        $isset =\Storage::disk('imagenes')->exists($filename);
        if($isset){
            $file=\Storage::disk('imagenes')->get($filename);
            return new Response($file,200);
        }else{
            $data = array(
                'status' => 'error',
                'code' => 404,
                'message' => 'No existe el archivo '
            );
        }
       
        return response()->json($data,$data['code']);
      
    }

    public function destroy($id, Request $request){
        $hash = $request->header('Authorization', null);
        
        $jwtAuth= new JwtAuth();
        $checkToken=$jwtAuth->checkToken($hash);
        
        if($checkToken){

            //comprobar si existe el registro
            $imagen=Imagen::find($id);
            $vercomentarios=Comentario::where('imagen_id',$id)->get();
              
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
                'log' => 'IMAGEN ELIMINADA '.$imagen->id,
                'prioridad' => 3,
                'comentarios' => $array_comentarios,
                'foto'=>$imagen->imagen_path,
                'usuario' => $imagen->user_id
            ];

            //borrar los comentarios primero
            $comentarios=Comentario::where('imagen_id',$id)->get()->each->delete();
        
            
            $log= new Log();
            $log->prioridad=3;
            $log->nombre='IMAGEN ELIMINADA '.$imagen->id;
            $log->save();

        
           
            //para descargar el archivo json con formato de contenido-id del mensaje
            $json_string = json_encode($array_contenido);
            $file =  "C:/wamp64/www/ApiSpinningCH/logs/IMAGEN ELIMINADA ".$imagen->id .'.json';
            file_put_contents($file, $json_string);

            //borrarlo
            \Storage::disk('imagenes')->delete($imagen->imagen_path);
            $imagen->delete();
            
            //devolver el registro borrado
             $data = array(
                'imagen' => $imagen,
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
}
