<?php

namespace App\Models;

use CodeIgniter\Model;

class AboutModel extends Model
{
    protected $table      = 'about';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = ['title', 'content', 'image'];

    protected $validationRules = [
        'title'   => 'required|max_length[100]',
        'content' => 'required',
    ];

    /** Etiquetas en español para los mensajes de validación del modelo. */
    protected function initialize()
    {
        parent::initialize();
        $this->validationRules = \App\Libraries\FieldLabels::apply($this->validationRules);
    }
}
