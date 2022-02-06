<?php
namespace App\Controller;

use App\Helper\ViewHelper;
use App\Helper\DbHelper;
use App\Model\Bosque;


class BosqueController
{
    var $db;
    var $view;

    function __construct()
    {
        //Conexión a la BBDD
        $dbHelper = new DbHelper();
        $this->db = $dbHelper->db;

        //Instancio el ViewHelper
        $viewHelper = new ViewHelper();
        $this->view = $viewHelper;
    }

    //Listado de bosques
    public function index(){

        //Permisos
        $this->view->permisos("bosques");

        //Recojo las bosques de la base de datos
        $rowset = $this->db->query("SELECT * FROM bosques ORDER BY fecha DESC");

        //Asigno resultados a un array de instancias del modelo
        $bosques = array();
        while ($row = $rowset->fetch(\PDO::FETCH_OBJ)){
            array_push($bosques,new Bosque($row));
        }

        $this->view->vista("admin","bosques/index", $bosques);

    }

    //Para activar o desactivar
    public function activar($id){

        //Permisos
        $this->view->permisos("bosques");

        //Obtengo la bosque
        $rowset = $this->db->query("SELECT * FROM bosques WHERE id='$id' LIMIT 1");
        $row = $rowset->fetch(\PDO::FETCH_OBJ);
        $bosque = new Bosque($row);

        if ($bosque->activo == 1){

            //Desactivo la bosque
            $consulta = $this->db->exec("UPDATE bosques SET activo=0 WHERE id='$id'");

            //Mensaje y redirección
            ($consulta > 0) ? //Compruebo consulta para ver que no ha habido errores
                $this->view->redireccionConMensaje("admin/bosques","green","La bosque <strong>$bosque->titulo</strong> se ha desactivado correctamente.") :
                $this->view->redireccionConMensaje("admin/bosques","red","Hubo un error al guardar en la base de datos.");
        }

        else{

            //Activo la bosque
            $consulta = $this->db->exec("UPDATE bosques SET activo=1 WHERE id='$id'");

            //Mensaje y redirección
            ($consulta > 0) ? //Compruebo consulta para ver que no ha habido errores
                $this->view->redireccionConMensaje("admin/bosques","green","La bosque <strong>$bosque->titulo</strong> se ha activado correctamente.") :
                $this->view->redireccionConMensaje("admin/bosques","red","Hubo un error al guardar en la base de datos.");
        }

    }

    //Para mostrar o no en la home
    public function home($id){

        //Permisos
        $this->view->permisos("bosques");

        //Obtengo la bosque
        $rowset = $this->db->query("SELECT * FROM bosques WHERE id='$id' LIMIT 1");
        $row = $rowset->fetch(\PDO::FETCH_OBJ);
        $bosque = new Bosque($row);

        if ($bosque->home == 1){

            //Quito la bosque de la home
            $consulta = $this->db->exec("UPDATE bosques SET home=0 WHERE id='$id'");

            //Mensaje y redirección
            ($consulta > 0) ? //Compruebo consulta para ver que no ha habido errores
                $this->view->redireccionConMensaje("admin/bosques","green","La bosque <strong>$bosque->titulo</strong> ya no se muestra en la home.") :
                $this->view->redireccionConMensaje("admin/bosques","red","Hubo un error al guardar en la base de datos.");
        }

        else{

            //Muestro la bosque en la home
            $consulta = $this->db->exec("UPDATE bosques SET home=1 WHERE id='$id'");

            //Mensaje y redirección
            ($consulta > 0) ? //Compruebo consulta para ver que no ha habido errores
                $this->view->redireccionConMensaje("admin/bosques","green","La bosque <strong>$bosque->titulo</strong> ahora se muestra en la home.") :
                $this->view->redireccionConMensaje("admin/bosques","red","Hubo un error al guardar en la base de datos.");
        }

    }

    public function borrar($id){

        //Permisos
        $this->view->permisos("bosques");

        //Obtengo la bosque
        $rowset = $this->db->query("SELECT * FROM bosques WHERE id='$id' LIMIT 1");
        $row = $rowset->fetch(\PDO::FETCH_OBJ);
        $bosque = new Bosque($row);

        //Borro la bosque
        $consulta = $this->db->exec("DELETE FROM bosques WHERE id='$id'");

        //Borro la imagen asociada
        $archivo = $_SESSION['public']."img/".$bosque->imagen;
        $texto_imagen = "";
        if (is_file($archivo)){
            unlink($archivo);
            $texto_imagen = " y se ha borrado la imagen asociada";
        }

        //Mensaje y redirección
        ($consulta > 0) ? //Compruebo consulta para ver que no ha habido errores
            $this->view->redireccionConMensaje("admin/bosques","green","La bosque se ha borrado correctamente$texto_imagen.") :
            $this->view->redireccionConMensaje("admin/bosques","red","Hubo un error al guardar en la base de datos.");

    }

    public function crear(){

        //Permisos
        $this->view->permisos("bosques");

        //Creo un nuevo usuario vacío
        $bosque = new Bosque();

        //Llamo a la ventana de edición
        $this->view->vista("admin","bosques/editar", $bosque);

    }

    public function editar($id){

        //Permisos
        $this->view->permisos("bosques");

        //Si ha pulsado el botón de guardar
        if (isset($_POST["guardar"])){

            //Recupero los datos del formulario
            $titulo = filter_input(INPUT_POST, "titulo", FILTER_SANITIZE_STRING);
            $entradilla = filter_input(INPUT_POST, "entradilla", FILTER_SANITIZE_STRING);
            $autor = filter_input(INPUT_POST, "autor", FILTER_SANITIZE_STRING);
            $fecha = filter_input(INPUT_POST, "fecha", FILTER_SANITIZE_STRING);
            $texto = filter_input(INPUT_POST, "texto", FILTER_SANITIZE_FULL_SPECIAL_CHARS);

            //Formato de fecha para SQL
            $fecha = \DateTime::createFromFormat("d-m-Y", $fecha)->format("Y-m-d H:i:s");

            //Genero slug (url amigable)
            $slug = $this->view->getSlug($titulo);

            //Imagen
            $imagen_recibida = $_FILES['imagen'];
            $imagen = ($_FILES['imagen']['name']) ? $_FILES['imagen']['name'] : "";
            $imagen_subida = ($_FILES['imagen']['name']) ? '/var/www/html'.$_SESSION['public']."img/".$_FILES['imagen']['name'] : "";
            $texto_img = ""; //Para el mensaje

            if ($id == "nuevo"){

                //Creo una nueva bosque
                $consulta = $this->db->exec("INSERT INTO bosques 
                    (titulo, entradilla, autor, fecha, texto, slug, imagen) VALUES 
                    ('$titulo','$entradilla','$autor','$fecha','$texto','$slug','$imagen')");

                //Subo la imagen
                if ($imagen){
                    if (is_uploaded_file($imagen_recibida['tmp_name']) && move_uploaded_file($imagen_recibida['tmp_name'], $imagen_subida)){
                        $texto_img = " La imagen se ha subido correctamente.";
                    }
                    else{
                        $texto_img = " Hubo un problema al subir la imagen.";
                    }
                }

                //Mensaje y redirección
                ($consulta > 0) ?
                    $this->view->redireccionConMensaje("admin/bosques","green","La bosque <strong>$titulo</strong> se creado correctamente.".$texto_img) :
                    $this->view->redireccionConMensaje("admin/bosques","red","Hubo un error al guardar en la base de datos.");
            }
            else{

                //Actualizo la bosque
                $this->db->exec("UPDATE bosques SET 
                    titulo='$titulo',entradilla='$entradilla',autor='$autor',
                    fecha='$fecha',texto='$texto',slug='$slug' WHERE id='$id'");

                //Subo y actualizo la imagen
                if ($imagen){
                    if (is_uploaded_file($imagen_recibida['tmp_name']) && move_uploaded_file($imagen_recibida['tmp_name'], $imagen_subida)){
                        $texto_img = " La imagen se ha subido correctamente.";
                        $this->db->exec("UPDATE bosques SET imagen='$imagen' WHERE id='$id'");
                    }
                    else{
                        $texto_img = " Hubo un problema al subir la imagen.";
                    }
                }

                //Mensaje y redirección
                $this->view->redireccionConMensaje("admin/bosques","green","La bosque <strong>$titulo</strong> se guardado correctamente.".$texto_img);

            }
        }

        //Si no, obtengo bosque y muestro la ventana de edición
        else{

            //Obtengo la bosque
            $rowset = $this->db->query("SELECT * FROM bosques WHERE id='$id' LIMIT 1");
            $row = $rowset->fetch(\PDO::FETCH_OBJ);
            $bosque = new Bosque($row);

            //Llamo a la ventana de edición
            $this->view->vista("admin","bosques/editar", $bosque);
        }

    }

}