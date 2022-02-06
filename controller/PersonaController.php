<?php
namespace App\Controller;

use App\Helper\ViewHelper;
use App\Helper\DbHelper;
use App\Model\Persona;


class PersonaController
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

    public function admin(){

        //Compruebo permisos
        $this->view->permisos();

        //LLamo a la vista
        $this->view->vista("admin","index");

    }

    public function entrar(){

        //Si ya está autenticado, le llevo a la página de inicio del panel
        if (isset($_SESSION['persona'])){

            $this->admin();

        }
        //Si ha pulsado el botón de acceder, tramito el formulario
        else if (isset($_POST["acceder"])){
            //echo ("hola acceder");
            //Recupero los datos del formulario
            $campo_persona = filter_input(INPUT_POST, "persona", FILTER_SANITIZE_STRING);
            $campo_clave = filter_input(INPUT_POST, "clave", FILTER_SANITIZE_STRING);
            //echo ("hola recupero");
            //Busco al usuario en la base de datos
            $rowset = $this->db->query("SELECT * FROM personas WHERE persona='$campo_persona' AND activo=1 LIMIT 1");
            //echo ("hola busco");
            //Asigno resultado a una instancia del modelo
            $row = $rowset->fetch(\PDO::FETCH_OBJ);
            $persona = new Persona($row);
            //echo ("hola asigno");
            //Si existe el usuario
            if ($persona->persona){
                //Compruebo la clave
                if (password_verify($campo_clave,$persona->clave)) {

                    //Asigno el usuario y los permisos la sesión
                    $_SESSION["persona"] = $persona->persona;
                    $_SESSION["personas"] = $persona->personas;
                    $_SESSION["bosques"] = $persona->bosques;

                    //Guardo la fecha de último acceso
                    $ahora = new \DateTime("now", new \DateTimeZone("Europe/Madrid"));
                    $fecha = $ahora->format("Y-m-d H:i:s");
                    $this->db->exec("UPDATE personas SET fecha_acceso='$fecha' WHERE persona='$campo_persona'");
                    echo("Hola ");
                    //Redirección con mensaje
                    $this->view->redireccionConMensaje("admin","green","Bienvenido al panel de administración.");
                }
                else{
                    //Redirección con mensaje
                    $this->view->redireccionConMensaje("admin","red","Contraseña incorrecta.");
                }
            }
            else{
                //Redirección con mensaje
                $this->view->redireccionConMensaje("admin","red","No existe ningúna persona con ese nombre.");
            }
        }
        //Le llevo a la página de acceso
        else{
            $this->view->vista("admin","personas/entrar");
        }

    }

    public function salir(){

        //Borro al usuario de la sesión
        unset($_SESSION['persona']);

        //Redirección con mensaje
        $this->view->redireccionConMensaje("admin","green","Te has desconectado con éxito.");

    }

    //Listado de personas
    public function index(){

        //Permisos
        $this->view->permisos("personas");

        //Recojo los personas de la base de datos
        $rowset = $this->db->query("SELECT * FROM personas ORDER BY persona ASC");

        //Asigno resultados a un array de instancias del modelo
        $persona = array();
        while ($row = $rowset->fetch(\PDO::FETCH_OBJ)){
            array_push($persona,new Persona($row));
        }

        $this->view->vista("admin","personas/index", $persona);

    }

    //Para activar o desactivar
    public function activar($id){

        //Permisos
        $this->view->permisos("personas");

        //Obtengo el usuario
        $rowset = $this->db->query("SELECT * FROM personas WHERE id='$id' LIMIT 1");
        $row = $rowset->fetch(\PDO::FETCH_OBJ);
        $persona = new Persona($row);

        if ($persona->activo == 1){

            //Desactivo el usuario
            $consulta = $this->db->exec("UPDATE personas SET activo=0 WHERE id='$id'");

            //Mensaje y redirección
            ($consulta > 0) ? //Compruebo consulta para ver que no ha habido errores
                $this->view->redireccionConMensaje("admin/personas","green","El usuario <strong>$persona->persona</strong> se ha desactivado correctamente.") :
                $this->view->redireccionConMensaje("admin/personas","red","Hubo un error al guardar en la base de datos.");
        }

        else{

            //Activo la persona
            $consulta = $this->db->exec("UPDATE personas SET activo=1 WHERE id='$id'");

            //Mensaje y redirección
            ($consulta > 0) ? //Compruebo consulta para ver que no ha habido errores
                $this->view->redireccionConMensaje("admin/personas","green","La persona <strong>$persona->persona</strong> se ha activado correctamente.") :
                $this->view->redireccionConMensaje("admin/personas","red","Hubo un error al guardar en la base de datos.");
        }

    }

    public function borrar($id){

        //Permisos
        $this->view->permisos("personas");

        //Borro la persona
        $consulta = $this->db->exec("DELETE FROM personas WHERE id='$id'");

        //Mensaje y redirección
        ($consulta > 0) ? //Compruebo consulta para ver que no ha habido errores
            $this->view->redireccionConMensaje("admin/personas","green","La persona se ha borrado correctamente.") :
            $this->view->redireccionConMensaje("admin/personas","red","Hubo un error al guardar en la base de datos.");

    }

    public function crear(){

        //Permisos
        $this->view->permisos("personas");

        //Creo un nuevo usuario vacío
        $persona = new Persona();

        //Llamo a la ventana de edición
        $this->view->vista("admin","personas/editar", $persona);

    }

    public function editar($id){

        //Permisos
        $this->view->permisos("personas");

        //Si ha pulsado el botón de guardar
        if (isset($_POST["guardar"])){

            //Recupero los datos del formulario
            $persona = filter_input(INPUT_POST, "persona", FILTER_SANITIZE_STRING);
            $clave = filter_input(INPUT_POST, "clave", FILTER_SANITIZE_STRING);
            $personas = (filter_input(INPUT_POST, 'personas', FILTER_SANITIZE_STRING) == 'on') ? 1 : 0;
            $bosques = (filter_input(INPUT_POST, 'bosques', FILTER_SANITIZE_STRING) == 'on') ? 1 : 0;
            $cambiar_clave = (filter_input(INPUT_POST, 'cambiar_clave', FILTER_SANITIZE_STRING) == 'on') ? 1 : 0;

            //Encripto la clave
            $clave_encriptada = ($clave) ? password_hash($clave,  PASSWORD_BCRYPT, ['cost'=>12]) : "";

            if ($id == "nuevo"){

                //Creo un nuevo usuario
                $this->db->exec("INSERT INTO personas (persona, clave, bosques, personas) VALUES ('$persona','$clave_encriptada',$bosques,$persona)");

                //Mensaje y redirección
                $this->view->redireccionConMensaje("admin/personas","green","La persona <strong>$persona</strong> se creado correctamente.");
            }
            else{

                //Actualizo la persona
                ($cambiar_clave) ?
                    $this->db->exec("UPDATE personas SET persona='$persona',clave='$clave_encriptada',bosques=$bosques,personas=$personas WHERE id='$id'") :
                    $this->db->exec("UPDATE personas SET persona='$persona',bosques=$bosques,personas=$personas WHERE id='$id'");

                //Mensaje y redirección
                $this->view->redireccionConMensaje("admin/personas","green","La persona <strong>$persona</strong> se actualizado correctamente.");
            }
        }

        //Si no, obtengo usuario y muestro la ventana de edición
        else{

            //Obtengo el usuario
            $rowset = $this->db->query("SELECT * FROM personas WHERE id='$id' LIMIT 1");
            $row = $rowset->fetch(\PDO::FETCH_OBJ);
            $persona = new Persona($row);

            //Llamo a la ventana de edición
            $this->view->vista("admin","personas/editar", $persona);
        }

    }


}