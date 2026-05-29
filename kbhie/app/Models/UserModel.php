<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table            = 'users';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $allowedFields    = [
        'email','phone','name','password_hash','avatar','status',
        'email_verified_at','phone_verified_at','last_login_at','meta',
    ];
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';
    protected $deletedField     = 'deleted_at';
    protected $dateFormat       = 'datetime';

    public function findByEmailOrPhone(string $identifier): ?array
    {
        $this->groupStart()
            ->where('email', $identifier)
            ->orWhere('phone', $identifier)
            ->groupEnd();
        $row = $this->first();
        return $row ?: null;
    }

    public function getRoles(int $userId): array
    {
        $rows = $this->db->table('user_roles ur')
            ->join('roles r', 'r.id = ur.role_id')
            ->where('ur.user_id', $userId)
            ->select('r.name')
            ->get()
            ->getResultArray();
        return array_column($rows, 'name');
    }

    public function attachRole(int $userId, string $roleName): void
    {
        $role = $this->db->table('roles')->where('name', $roleName)->get()->getRow();
        if (! $role) return;
        $exists = $this->db->table('user_roles')
            ->where(['user_id' => $userId, 'role_id' => $role->id])
            ->countAllResults() > 0;
        if (! $exists) {
            $this->db->table('user_roles')->insert([
                'user_id' => $userId,
                'role_id' => $role->id,
            ]);
        }
    }
}
