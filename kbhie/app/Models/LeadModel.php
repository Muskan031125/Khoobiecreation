<?php

namespace App\Models;

use CodeIgniter\Model;

class LeadModel extends Model
{
    protected $table         = 'leads';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = [
        'anon_id','user_id','email','phone','name','city','pincode',
        'source','landing_url',
        'utm_source','utm_medium','utm_campaign','utm_term','utm_content',
        'fbclid','gclid','ip','user_agent',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'captured_at';
    protected $updatedField  = 'updated_at';
    protected $dateFormat    = 'datetime';

    /**
     * Find existing lead by best available identifier, otherwise create.
     * Returns the lead row.
     */
    public function upsert(array $data): array
    {
        $existing = null;
        if (! empty($data['email'])) {
            $existing = $this->where('email', $data['email'])->orderBy('id', 'DESC')->first();
        }
        if (! $existing && ! empty($data['phone'])) {
            $existing = $this->where('phone', $data['phone'])->orderBy('id', 'DESC')->first();
        }
        if (! $existing && ! empty($data['anon_id'])) {
            $existing = $this->where('anon_id', $data['anon_id'])->orderBy('id', 'DESC')->first();
        }
        if ($existing) {
            $merge = array_filter($data, static fn ($v) => $v !== null && $v !== '');
            unset($merge['id']);
            $this->update($existing['id'], $merge);
            return $this->find($existing['id']);
        }
        $id = $this->insert($data, true);
        return $this->find($id);
    }
}
