<?php

namespace App\Models;

use CodeIgniter\Model;

class AdminPasswordResetModel extends Model
{
    protected $table      = 'admin_password_resets';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = ['aemail', 'token', 'expires_at', 'used'];

    public function createToken(string $email): string
    {
        $plainToken = bin2hex(random_bytes(32));
        $hashedToken = hash('sha256', $plainToken);

        // Invalidar tokens anteriores del mismo email
        $this->where('aemail', $email)->set('used', 1)->update();

        $this->insert([
            'aemail'     => $email,
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
