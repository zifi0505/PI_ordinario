<?php
$server = "localhost";
$user = "root";
$pass = "";
$db = "api";

$conexion = new mysqli($server, $user, $pass, $db);

if($conexion->connect_error){
    die("error" .$conexion->connect_error);
}else{
    echo "";
}

header("content-type: application/json");
$metodo= $_SERVER['REQUEST_METHOD'];

$path= isset($_SERVER['PATH_INFO'])?$_SERVER['PATH_INFO']:'/';

$buscarId = explode('/', $path);

$id= ($path!=='/') ? end($buscarId):null;

switch ($metodo){

    case 'GET':
        consultaSelect($conexion, $id);
        break;
    case 'POST':
        insertar($conexion);
        break;
     case 'PUT':
        actualizar($conexion, $id);
        break;
    case 'DELETE':
        borrar($conexion, $id);
        break;
    default:
        echo "Metodo no permitido";
        break;   
}


function consultaSelect($conexion){
    $sql = ($id === null) ? "SELECT id FROM usuarios" : "SELECT * FROM usuarios WHERE id=$id";
    $resultado = $conexion->query($sql); 

    if($resultado){
        $datos= array();
        while($fila = $resultado->fetch_assoc()){
            $datos[] = $fila;

        }
        echo json_encode($datos);

    }

}

function insertar($conexion){
    $dato= json_decode(file_get_contents('php://input'),true);
    $nombre= $dato['nombre'];

    $sql= "INSERT INTO usuarios(nombre) VALUES ('$nombre')";
    $resultado= $conexion->query($sql);

    if($resultado){
        $datos['id'] = $conexion->insert_id;
        echo json_encode($datos);
  
    }else{
        echo json_encode(array('error'=> 'Error al crear usuario '));
    }
}

function borrar($conexion, $id){

    $sql= "DELETE FROM usuarios WHERE id =$id";
    $resultado= $conexion->query($sql);

    if($resultado){
        
        echo json_encode(array('mensaje'=> 'usuario eliminado'));
  
    }else{
        echo json_encode(array('error'=> 'Error al crear usuario '));
    }

}
function actualizar($conexion, $id){

    $dato= json_decode(file_get_contents('php://input'),true);
    $nombre= $dato['nombre'];

    echo "El id a editar es:. ". $id; "con el dato".$nombre;

    $sql= "UPDATE usuarios SET nombre = '$nombre' WHERE id = $id";
    $resultado= $conexion->query($sql);

    if($resultado){
        
        echo json_encode(array('mensaje'=> 'usuario actualizado'));
  
    }else{
        echo json_encode(array('error'=> 'Error al actualizar usuario '));
    }

}

?>