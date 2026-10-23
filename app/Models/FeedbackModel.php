<?php

namespace App\Models;

use CodeIgniter\Model;

class FeedbackModel extends Model
{
    protected $table      = 'feedback';
    protected $primaryKey = 'fid';
    protected $returnType = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = ['uid', 'fdescription', 'status'];

    protected $validationRules = [
        'uid'          => 'required|is_natural_no_zero',
        'fdescription' => 'required|max_length[300]',
    ];

    /** Feedback aprobado con datos del usuario */
    public function active(): static
    {
        return $this->select('feedback.*, user.uname, user.uimage')
                    ->join('user', 'user.uid = feedback.uid')
                    ->where('feedback.status', 1);
    }

    /** Todos los feedbacks con datos de usuario (para admin) */
    public function withUser(): static
    {
        return $this->select('feedback.*, user.uname, user.uemail')
                    ->join('user', 'user.uid = feedback.uid');
    }
}
