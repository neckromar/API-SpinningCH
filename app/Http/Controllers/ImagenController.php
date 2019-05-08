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

class ImagenController extends Controller
{
    public function index(Request $request) {
        $imagenes = Imagen::all()->load('user')->load('comments');
       
        return response()->json(array(
                    'imagenes' =>  $imagenes,
                    'status' => 'success'
                        ), 200);
    }

    public function show($id) {
        $imagenes = Imagen::find($id)->load('user')->load('comments');
        if(is_object($imagenes)){
            $imagenes = Imagen::find($id)->load('user')->load('comments');
            $comentarios = Comentario::where('imagen_id',$id)->get()->load('usercomentario');

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
                $imagen->save();
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
            
            //borrar los comentarios primero
            $comentarios=Comentario::where('imagen_id',$id)->get()->each->delete();
        
        
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
