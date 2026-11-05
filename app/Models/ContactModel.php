<?php

namespace App\Models;

use CodeIgniter\Model;

class ContactModel extends Model
{
    protected $table      = 'contact';
    protected $primaryKey = 'cid';
    protected $returnType = 'array';
    protected $allowedFields = ['name', 'email', 'phone', 'subject', 'message'];

    protected $validationRules = [
        'name'    => 'required|max_length[100]',
        'email'   => 'required|valid_email',
        'phone'   => 'permit_empty|max_length[20]',
        'subject' => 'required|max_length[100]',
        'message' => 'required',
    ];

    /** Etiquetas en español para los mensajes de validación del modelo. */
    protected function initialize()
    {
        parent::initialize();
        $this->validationRules = \App\Libraries\FieldLabels::apply($this->validationRules);
    }
}
