<?php

namespace App\Controllers;

class Home extends BaseController
{
    public function index(): string
    {
        // Conectamos a la base de datos de forma directa
        $db = \Config\Database::connect();
        
        // Hacemos un SELECT * FROM tabla_prueba
        $query = $db->table('tabla_prueba')->get();
        
        // Obtenemos los resultados en un arreglo
        $resultados = $query->getResultArray();

        // Si hay datos, los preparamos para la vista
        $data['registros'] = $resultados;

        return view('welcome_message', $data);
    }
}
