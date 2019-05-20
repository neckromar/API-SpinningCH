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

class VideoController extends Controller
{
    public function index(Request $request) {
        $videos = Video::all()->load('user')->load('comments');
       
        return response()->json(array(
                    'videos' =>  $videos,
                    'status' => 'success'
                        ), 200);
    }
    public function show($id) {
        $videos = Video::find($id)->load('user')->load('comments');
        if(is_object($videos)){
            $videos = Video::find($id)->load('user')->load('comments');
            $comentarios = Comentario::where('video_id',$id)->get()->load('usercomentario');

            return response()->json(array(
                'videos' => $videos,
                'comentarios' => $comentarios,
                'status' => 'success'
                    ), 200);
        }
        else{
            return response()->json(array(
                'message' => 'El video no existe',
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
                         'title' => 'required',
                        'description' => 'required'
            ]);
            if ($validate->fails()) {
                return response()->json($validate->errors(), 400);
            }
            //guardar el coche
            $video = new Video();
            $video->user_id = $user->sub;
            $video->description = $params->description;
            $video->title = $params->title;
            $video->status = "ACTIVAR";
            $video->video_path = $params->video_path;
            $video->miniatura = $params->miniatura;

            if($params->video_path == "" || $params->video_path == null ||  $params->miniatura == "" || $params->miniatura == null){
               
                    $data = array(
                        'message' => 'Hay que subir un video y la miniatura',
                        'status' => 'error',
                        'code' => 400
                    );
            }else{

 
                $array_contenido=[
                    'log' => 'NUEVO VIDEO '.$video->id,
                    'titulo' => $video->title,
                    'contenido' =>$video->description,
                    'miniatura'=>$video->miniatura,
                    'video_path' =>$video->video_path ,
                    'prioridad' => 1,
                    'usuario' => $video->user_id
                ];

                $video->save();
                $log= new Log();
                $log->prioridad=1;
                $log->nombre='NUEVO VIDEO '.$video->id;
                $log->save();

                //para descargar el archivo json con formato de contenido-id del mensaje
                $json_string = json_encode($array_contenido);
                $file =  "C:/wamp64/www/ApiSpinningCH/logs/NUEVO VIDEO ".$video->id .'.json';
                file_put_contents($file, $json_string);

                $data = array(
                    'video' => $video,
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

    public function upload(Request $request){

        //re4coger datos de la peticion
        $video=$request->file('file0');

        //validate

            $validate=\Validator::make($request->all(),[
            'file0' => 'required'
        ]);


        //guardar imagen
        if( !$video || $validate->fails())
        {
            $data = array(
                'status' => 'error',
                'code' => 400,
                'message' => 'Error al subir el archivo '
            );
       
        }else{
          
            $video_path=time().$video->getClientOriginalName();
            \Storage::disk('videos')->put($video_path, \File::get($video));
 
            $data=array(
                'code' => 200,
                'video_path' => $video_path,
                'status' => 'success'
            );
        }
    

      
        return response()->json($data,$data['code']);
    }

    public function uploadminiatura(Request $request){

        //re4coger datos de la peticion
        $miniatura=$request->file('file0');

        //validate

            $validate=\Validator::make($request->all(),[
            'file0' => 'required|image|mimes:jpg,jpeg,png,gif'
        ]);


        //guardar imagen
        if( !$miniatura || $validate->fails())
        {
            $data = array(
                'status' => 'error',
                'code' => 400,
                'message' => 'Error al subir la miniatura '
            );
       
        }else{
          
            $miniatura_name=time().$miniatura->getClientOriginalName();
            \Storage::disk('miniaturas')->put($miniatura_name, \File::get($miniatura));
 
            $data=array(
                'code' => 200,
                'miniatura' => $miniatura_name,
                'status' => 'success'
            );
        }

      
        return response()->json($data,$data['code']);
    }


    public function getVideo($filename){

        $isset =\Storage::disk('videos')->exists($filename);
        if($isset){
            $file=\Storage::disk('videos')->get($filename);
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
    public function getMiniatura($filename){

        $isset =\Storage::disk('miniaturas')->exists($filename);
        if($isset){
            $file=\Storage::disk('miniaturas')->get($filename);
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
            $video=Video::find($id);


            //PARA HACER EL LOG Y CREAR EL FICHERO JSON
            $vercomentarios=Comentario::where('video_id',$id)->get();
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
                'log' => 'VIDEO ELIMINADO '.$video->id,
                'titulo' => $video->title,
                'contenido' =>$video->description,
                'miniatura'=>$video->miniatura,
                'video_path' =>$video->video_path,
                'prioridad' => 3,
                'comentarios' => $array_comentarios,
                'usuario' => $video->user_id
            ];

            $log= new Log();
            $log->prioridad=3;
            $log->nombre='VIDEO ELIMINADO '.$video->id;
            $log->save();

            //para descargar el archivo json con formato de contenido-id del mensaje
            $json_string = json_encode($array_contenido);
            $file =  "C:/wamp64/www/ApiSpinningCH/logs/VIDEO ELIMINADO ".$video->id .'.json';
            file_put_contents($file, $json_string);




            //borrar los comentarios primero
            $comentarios=Comentario::where('video_id',$id)->get()->each->delete();
        
        
            //borrarlo
            \Storage::disk('videos')->delete($video->video_path);
            \Storage::disk('miniaturas')->delete($video->miniatura);
            $video->delete();
            
            //devolver el registro borrado
             $data = array(
                'video' => $video,
                'message' => 'video borrado correctamente',
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
