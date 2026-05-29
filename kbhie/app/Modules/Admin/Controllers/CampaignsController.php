<?php

namespace App\Modules\Admin\Controllers;

use App\Libraries\LLM\LLMService;
use App\Libraries\Notifications\NotificationService;
use Config\Database;

class CampaignsController extends BaseAdminController
{
    public function index()
    {
        $rows = Database::connect()->table('campaigns')->orderBy('id', 'DESC')->limit(50)->get()->getResultArray();
        return $this->view('App\Modules\Admin\Views\campaigns_index', [
            'page' => ['title' => 'Campaigns'],
            'rows' => $rows,
        ]);
    }

    public function new() { return $this->editForm(null); }
    public function edit($id) {
        $row = Database::connect()->table('campaigns')->where('id', (int) $id)->get()->getRowArray();
        if (! $row) return redirect()->to('/admin/campaigns');
        return $this->editForm($row);
    }
    private function editForm(?array $row)
    {
        return $this->view('App\Modules\Admin\Views\campaigns_edit', [
            'page' => ['title' => $row ? 'Edit campaign' : 'New campaign'],
            'row'  => $row,
        ]);
    }

    public function save()
    {
        $db = Database::connect();
        $id = (int) $this->request->getPost('id');
        $data = [
            'name'         => $this->request->getPost('name') ?: 'Untitled',
            'subject'      => $this->request->getPost('subject') ?: '',
            'channel'      => $this->request->getPost('channel') ?: 'email',
            'body_html'    => $this->request->getPost('body_html'),
            'audience'     => $this->request->getPost('audience') ?: 'all',
            'audience_arg' => $this->request->getPost('audience_arg'),
            'ai_generated' => (int) $this->request->getPost('ai_generated'),
            'status'       => $this->request->getPost('status') ?: 'draft',
            'scheduled_at' => $this->request->getPost('scheduled_at') ?: null,
            'created_by'   => session('user')['id'] ?? null,
        ];
        if ($id) $db->table('campaigns')->where('id', $id)->update($data);
        else     { $db->table('campaigns')->insert($data); $id = (int) $db->insertID(); }
        return redirect()->to('/admin/campaigns/' . $id . '/edit')->with('success', 'Saved.');
    }

    public function aiDraft()
    {
        $audience = (string) $this->request->getPost('audience');
        $goal     = (string) $this->request->getPost('goal');
        $channel  = (string) $this->request->getPost('channel') ?: 'email';

        $system = 'You write marketing campaigns for Khoobie, a screen-free kids learning brand in India. Voice: warm, India-rooted, parent-trustworthy. Indian English. For email: write subject + HTML body (use inline styles, simple table layout). For WhatsApp: ≤300 chars, no HTML. For SMS: ≤160 chars. Return STRICT JSON: {"subject":"...","body":"..."}';
        $prompt = "Channel: {$channel}\nAudience: {$audience}\nGoal: {$goal}\n\nGenerate the campaign now.";
        $res = (new LLMService())->complete($prompt, ['max_tokens' => 1500, 'temperature' => 0.7, 'system' => $system]);
        $text = (string) ($res['text'] ?? '');
        if (preg_match('/\{.*\}/s', $text, $m)) {
            $j = json_decode($m[0], true);
            if (is_array($j)) return $this->response->setJSON(['ok' => true, 'subject' => $j['subject'] ?? '', 'body' => $j['body'] ?? '']);
        }
        return $this->response->setJSON(['ok' => false, 'error' => 'AI could not produce structured output.']);
    }

    public function send($id)
    {
        $db = Database::connect();
        $c  = $db->table('campaigns')->where('id', (int) $id)->get()->getRowArray();
        if (! $c) return redirect()->to('/admin/campaigns');

        $sql = match ($c['audience']) {
            'active_customers' => "SELECT name, email, phone FROM users WHERE email IS NOT NULL",
            'recent_buyers'    => "SELECT DISTINCT u.name, u.email, u.phone FROM users u JOIN orders o ON o.user_id=u.id WHERE o.created_at >= DATE_SUB(NOW(), INTERVAL 60 DAY) AND o.status='paid'",
            'by_city'          => "SELECT DISTINCT u.name, u.email, u.phone FROM users u JOIN addresses a ON a.user_id=u.id WHERE a.city = " . $db->escape($c['audience_arg']),
            'unverified'       => "SELECT name, email, phone FROM users WHERE email_verified_at IS NULL",
            'abandoned_cart'   => "SELECT DISTINCT u.name, u.email, u.phone FROM users u JOIN carts ca ON ca.user_id=u.id WHERE ca.item_count > 0 AND ca.updated_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)",
            default            => "SELECT name, email, phone FROM users WHERE email IS NOT NULL",
        };
        $recipients = $db->query($sql)->getResultArray();

        $db->table('campaigns')->where('id', $id)->update([
            'status' => 'sending', 'recipients_n' => count($recipients),
        ]);

        $notif = new NotificationService();
        $sent = 0;
        foreach ($recipients as $r) {
            $to = $c['channel'] === 'email' ? $r['email'] : $r['phone'];
            if (! $to) continue;
            try {
                $notif->send($c['channel'], $to, 'campaign.broadcast', [
                    'subject' => $c['subject'],
                    'message' => $c['body_html'],
                    'name'    => $r['name'],
                ]);
                $sent++;
            } catch (\Throwable $e) {}
        }

        $db->table('campaigns')->where('id', $id)->update([
            'status' => 'sent', 'sent_at' => date('Y-m-d H:i:s'), 'recipients_n' => $sent,
        ]);

        return redirect()->to('/admin/campaigns/' . $id . '/edit')->with('success', "Sent to {$sent} recipients.");
    }
}
