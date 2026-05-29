<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run()
    {
        // Create a default super_admin login. Change in production!
        $email = 'admin@khoobie.com';
        $existing = $this->db->table('users')->where('email', $email)->get()->getRow();
        if ($existing) return;

        $this->db->table('users')->insert([
            'email'             => $email,
            'name'              => 'Khoobie Admin',
            'password_hash'     => password_hash('khoobie@2026', PASSWORD_BCRYPT),
            'status'            => 'active',
            'email_verified_at' => date('Y-m-d H:i:s'),
        ]);
        $userId = (int) $this->db->insertID();

        $role = $this->db->table('roles')->where('name', 'super_admin')->get()->getRow();
        if ($role) {
            $this->db->table('user_roles')->insert([
                'user_id' => $userId,
                'role_id' => $role->id,
            ]);
        }
    }
}
