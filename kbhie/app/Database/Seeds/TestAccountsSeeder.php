<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Seeds three convenience test accounts for local QA.
 *   Customer : customer@khoobie.com / pass1234   (role: customer)
 *   Partner  : partner@khoobie.com  / pass1234   (role: partner, linked to "Demo Partner Co.")
 *   Staff    : staff@khoobie.com    / pass1234   (role: staff — read-only order desk)
 *
 * Super-admin remains: admin@khoobie.com / khoobie@2026 (seeded by AdminUserSeeder)
 */
class TestAccountsSeeder extends Seeder
{
    public function run()
    {
        $db = $this->db;

        $accounts = [
            [
                'email'         => 'customer@khoobie.com',
                'phone'         => '9000000001',
                'name'          => 'Test Customer',
                'password'      => 'pass1234',
                'roles'         => ['customer'],
                'address'       => [
                    'type'    => 'shipping',
                    'name'    => 'Test Customer',
                    'phone'   => '9000000001',
                    'line1'   => 'Flat 101, Demo Apartments',
                    'line2'   => 'Sector 18',
                    'city'    => 'Noida',
                    'state'   => 'Uttar Pradesh',
                    'pincode' => '201301',
                    'country' => 'IN',
                    'is_default' => 1,
                ],
            ],
            [
                'email'    => 'partner@khoobie.com',
                'phone'    => '9000000002',
                'name'     => 'Demo Partner Owner',
                'password' => 'pass1234',
                'roles'    => ['partner'],
                'partner'  => [
                    'company_name'      => 'Demo Partner Co.',
                    'contact_name'      => 'Demo Partner Owner',
                    'email'             => 'partner@khoobie.com',
                    'phone'             => '9000000002',
                    'address_line1'     => '12 Crafty Lane',
                    'city'              => 'Noida',
                    'state'             => 'Uttar Pradesh',
                    'pincode'           => '201307',
                    'fulfillment_type'  => 'drop_ship',
                    'commission_pct'    => 15,
                    'status'            => 'active',
                ],
            ],
            [
                'email'    => 'staff@khoobie.com',
                'phone'    => '9000000003',
                'name'     => 'Staff Order Desk',
                'password' => 'pass1234',
                'roles'    => ['staff'],
            ],
        ];

        foreach ($accounts as $a) {
            if ($db->table('users')->where('email', $a['email'])->countAllResults() > 0) continue;

            $db->table('users')->insert([
                'email'             => $a['email'],
                'phone'             => $a['phone'],
                'name'              => $a['name'],
                'password_hash'     => password_hash($a['password'], PASSWORD_BCRYPT),
                'status'            => 'active',
                'email_verified_at' => date('Y-m-d H:i:s'),
                'phone_verified_at' => date('Y-m-d H:i:s'),
            ]);
            $userId = (int) $db->insertID();

            foreach ($a['roles'] as $roleName) {
                $role = $db->table('roles')->where('name', $roleName)->get()->getRow();
                if ($role) {
                    $db->table('user_roles')->ignore(true)->insert([
                        'user_id' => $userId,
                        'role_id' => $role->id,
                    ]);
                }
            }

            if (! empty($a['address'])) {
                $db->table('addresses')->insert(array_merge(['user_id' => $userId], $a['address']));
            }

            if (! empty($a['partner'])) {
                if ($db->table('partners')->where('email', $a['partner']['email'])->countAllResults() === 0) {
                    $db->table('partners')->insert($a['partner']);
                }
                $partner = $db->table('partners')->where('email', $a['partner']['email'])->get()->getRowArray();
                $db->table('partner_users')->ignore(true)->insert([
                    'partner_id' => $partner['id'],
                    'user_id'    => $userId,
                    'role'       => 'owner',
                    'is_active'  => 1,
                ]);
            }

            // Loyalty account
            if (in_array('customer', $a['roles'], true)) {
                $db->table('loyalty_accounts')->ignore(true)->insert([
                    'user_id'        => $userId,
                    'points_balance' => 250,
                    'lifetime_points'=> 250,
                    'tier'           => 'bronze',
                ]);
            }
        }
    }
}
