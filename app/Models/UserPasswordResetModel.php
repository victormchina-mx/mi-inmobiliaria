<?php

namespace App\Models;

use CodeIgniter\Model;

class UserPasswordResetModel extends Model
{
    protected $table      = 'user_password_resets';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = ['uemail', 'token', 'expires_at', 'used'];

    public function createToken(string $email): string
    {
        $plainToken  = bin2hex(random_bytes(32));
        $hashedToken = hash('sha256', $plainToken);

        $this->where('uemail', $email)->set('used', 1)->update();

        $this->insert([
            'uemail'     => $email,
            'token'      => $hashedToken,
            'expires_at' => date('Y-m-d H:i:s', strtotime('+1 hour')),
            'used'       => 0,
        ]);

        return $plainToken;
    }

    public function findValidToken(string $plainToken): ?array
    {
        $hashedToken = hash('sha256', $plainToken);

        return $this->where('token', $hashedToken)
                    ->where('used', 0)
                    ->where('expires_at >=', date('Y-m-d H:i:s'))
                    ->first();
    }

    public function invalidateToken(string $plainToken): void
    {
        $hashedToken = hash('sha256', $plainToken);
        $this->where('token', $hashedToken)->set('used', 1)->update();
    }
}
