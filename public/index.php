<?php
namespace App;

//Inicializo sesión para poder traspasar variables entre páginas
session_start();

//Incluyo los controladores que voy a utilizar para que seran cargados por Autoload
use App\Controller\AppController;//Front-end
use App\Controller\BosqueController;//Back-end de bosques
use App\Controller\PersonaController;//Autentificación y back-end de personas

//echo password_hash("1234Abc!",  PASSWORD_BCRYPT, ['cost'=>12]);
/*
 * Asigno a sesión las rutas de las carpetas public y home, necesarias tanto para las rutas como para
 * poder enlazar imágenes y archivos css, js
 */
$_SESSION['public'] = '/practica_cms/public/';
$_SESSION['home'] = $_SESSION['public'].'index.php/';

//Defino y llamo a la función que autocargará las clases cuando se instancien
spl_autoload_register('App\autoload');

function autoload($clase,$dir=null){

    //Directorio raíz de mi proyecto
    if (is_null($dir)){
        $dirname = str_replace('/public', '', dirname(__FILE__));
        $dir = realpath($dirname);
    }

    //Escaneo en busca de la clase de forma recursiva
    foreach (scandir($dir) as $file){
        //Si es un directorio (y no es de sistema) accedo y
        //busco la clase dentro de él
        if (is_dir($dir."/".$file) AND substr($file, 0, 1) !== '.'){
            autoload($clase, $dir."/".$file);
        }
        //Si es un fichero y el nombr conicide con el de la clase
        else if (is_file($dir."/".$file) AND $file == substr(strrchr($clase, "\\"), 1).".php"){
            require($dir."/".$file);
        }
    }

}

//Para invocar al controlador en cada ruta
function controlador($nombre=null){

    switch($nombre){
        default: return new AppController;
        case "bosques": return new BosqueController;
        case "personas": return new PersonaController;
    }
}

//Quito la ruta de la home a la que me están pidiendo
$ruta = str_replace($_SESSION['home'], '', $_SERVER['REQUEST_URI']);

//Encamino cada ruta al controlador y acción correspondientes
switch ($ruta){

    //Front-end
    case "":
    case "/":
        controlador()->index();
        break;
    case "acerca-de":
        controlador()->acercade();
        break;
    case "bosques":
        controlador()->bosques();
        break;
    case (strpos($ruta,"bosque/") === 0)://si la ruta empieza por "bosque/"
        controlador()->bosque(str_replace("bosque/","",$ruta));//El parámetro es lo que haya después de "bosque/"
        break;

    //Back-end
    case "admin":
    case "admin/entrar":
        controlador("personas")->entrar();
        break;
    case "admin/salir":
        controlador("personas")->salir();
        break;
    case "admin/personas":
        controlador("personas")->index();
        break;
    case "admin/personas/crear":
        controlador("personas")->crear();
        break;
    case (strpos($ruta,"admin/personas/editar/") === 0):
        controlador("personas")->editar(str_replace("admin/personas/editar/","",$ruta));
        break;
    case (strpos($ruta,"admin/personas/activar/") === 0):
        controlador("personas")->activar(str_replace("admin/personas/activar/","",$ruta));
        break;
    case (strpos($ruta,"admin/personas/borrar/") === 0):
        controlador("personas")->borrar(str_replace("admin/personas/borrar/","",$ruta));
        break;
    case "admin/bosques":
        controlador("bosques")->index();
        break;
    case "admin/bosques/crear":
        controlador("bosques")->crear();
        break;
    case (strpos($ruta,"admin/bosques/editar/") === 0):
        controlador("bosques")->editar(str_replace("admin/bosques/editar/","",$ruta));
        break;
    case (strpos($ruta,"admin/bosques/activar/") === 0):
        controlador("bosques")->activar(str_replace("admin/bosques/activar/","",$ruta));
        break;
    case (strpos($ruta,"admin/bosques/home/") === 0):
        controlador("bosques")->home(str_replace("admin/bosques/home/","",$ruta));
        break;
    case (strpos($ruta,"admin/bosques/borrar/") === 0):
        controlador("bosques")->borrar(str_replace("admin/bosques/borrar/","",$ruta));
        break;
    case (strpos($ruta,"admin/") === 0):
        controlador("personas")->entrar();
        break;

    //Resto de rutas
    default:
        controlador()->index();

}