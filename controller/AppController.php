<?php
namespace App\Controller;

use App\Model\Bosque;
use App\Helper\ViewHelper;
use App\Helper\DbHelper;


class AppController
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

    public function index(){

        //Consulta a la bbdd
        $rowset = $this->db->query("SELECT * FROM bosques WHERE activo=1 AND home=1 ORDER BY fecha DESC");

        //Asigno resultados a un array de instancias del modelo
        $bosques = array();
        while ($row = $rowset->fetch(\PDO::FETCH_OBJ)){
            array_push($bosques,new Bosque($row));
        }

        //Llamo a la vista
        $this->view->vista("app", "index", $bosques);
    }

    public function acercade(){

        //Llamo a la vista
        $this->view->vista("app", "acerca-de");

    }

    public function bosques(){

        //Consulta a la bbdd
        $rowset = $this->db->query("SELECT * FROM bosques WHERE activo=1 ORDER BY fecha DESC");

        //Asigno resultados a un array de instancias del modelo
        $bosques = array();
        while ($row = $rowset->fetch(\PDO::FETCH_OBJ)){
            array_push($bosques,new Bosque($row));
        }

        //Llamo a la vista
        $this->view->vista("app", "bosques", $bosques);

    }

    public function bosque($slug){

        //Consulta a la bbdd
        $rowset = $this->db->query("SELECT * FROM bosques WHERE activo=1 AND slug='$slug' LIMIT 1");

        //Asigno resultado a una instancia del modelo
        $row = $rowset->fetch(\PDO::FETCH_OBJ);
        $bosque = new Bosque($row);

        //Llamo a la vista
        $this->view->vista("app", "bosque", $bosque);

    }
}