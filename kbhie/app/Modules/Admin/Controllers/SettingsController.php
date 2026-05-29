<?php
namespace App\Modules\Admin\Controllers;

use Config\Database;

class SettingsController extends BaseAdminController
{
    public function index()
    {
        $rows = Database::connect()->table('settings')->orderBy('group_key')->orderBy('key')->get()->getResultArray();
        $grouped = [];
        foreach ($rows as $r) $grouped[$r['group_key']][] = $r;
        return $this->view('App\Modules\Admin\Views\settings', [
            'page' => ['title' => 'Settings'],
            'grouped' => $grouped,
        ]);
    }

    public function save()
    {
        $db = Database::connect();
        foreach (($this->request->getPost('settings') ?? []) as $id => $value) {
            $db->table('settings')->where('id', (int) $id)->update(['value' => $value]);
        }
        return redirect()->to('/admin/settings')->with('success', 'Settings saved.');
    }
}
