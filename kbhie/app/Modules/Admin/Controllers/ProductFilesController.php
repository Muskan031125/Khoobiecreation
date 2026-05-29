<?php

namespace App\Modules\Admin\Controllers;

use Config\Database;

/**
 * Upload + delete digital files attached to a product.
 * Files go to writable/uploads/product_files/{product_id}/{filename}.
 */
class ProductFilesController extends BaseAdminController
{
    private const ALLOWED = ['pdf','epub','zip','mp3','mp4','jpg','jpeg','png','docx','xlsx','pptx'];
    private const MAX_MB  = 50;

    public function upload($productId)
    {
        $productId = (int) $productId;
        $db = Database::connect();
        $product = $db->table('products')->where('id', $productId)->get()->getRow();
        if (! $product) return $this->fail('Product not found.');

        $file = $this->request->getFile('file');
        if (! $file || ! $file->isValid()) return $this->fail($file ? $file->getErrorString() : 'No file received.');

        $ext = strtolower($file->getExtension() ?: pathinfo($file->getClientName(), PATHINFO_EXTENSION));
        if (! in_array($ext, self::ALLOWED, true)) {
            return $this->fail('File type .' . $ext . ' not allowed. Try: ' . implode(', ', self::ALLOWED));
        }
        if ($file->getSize() > self::MAX_MB * 1024 * 1024) {
            return $this->fail('File too large (max ' . self::MAX_MB . 'MB).');
        }

        $dir = WRITEPATH . 'uploads/product_files/' . $productId;
        if (! is_dir($dir)) @mkdir($dir, 0775, true);

        $clean    = preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientName());
        $newName  = time() . '-' . $clean;
        $file->move($dir, $newName);

        $relPath  = "uploads/product_files/{$productId}/{$newName}";
        $publicUrl = base_url('files/' . $productId . '/' . $newName);

        $db->table('product_files')->insert([
            'product_id'      => $productId,
            'file_url'        => $publicUrl,
            'file_name'       => $clean,
            'file_size_bytes' => $file->getSize(),
            'mime_type'       => $file->getMimeType() ?? null,
            'is_sample'       => (int) $this->request->getPost('is_sample'),
        ]);

        return $this->response->setJSON([
            'ok'        => true,
            'file'      => [
                'id'        => (int) $db->insertID(),
                'file_name' => $clean,
                'file_url'  => $publicUrl,
                'size'      => $file->getSize(),
            ],
        ]);
    }

    public function delete($fileId)
    {
        $db  = Database::connect();
        $row = $db->table('product_files')->where('id', (int) $fileId)->get()->getRow();
        if (! $row) return $this->fail('File not found.');

        // Unlink from disk
        $relative = str_replace(base_url('files/'), '', $row->file_url);
        $diskPath = WRITEPATH . 'uploads/product_files/' . $relative;
        if (file_exists($diskPath)) @unlink($diskPath);

        $db->table('product_files')->where('id', (int) $fileId)->delete();
        return $this->response->setJSON(['ok' => true]);
    }

    private function fail(string $msg)
    {
        return $this->response->setJSON(['ok' => false, 'error' => $msg]);
    }
}
