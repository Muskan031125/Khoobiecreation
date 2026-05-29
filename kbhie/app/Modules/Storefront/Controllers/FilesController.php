<?php

namespace App\Modules\Storefront\Controllers;

/**
 * Serves uploaded product files from writable/uploads/product_files/.
 * Direct-access route — in production, prefer signed S3/CloudFront URLs.
 * For now, the DigitalDeliveryService /download/{token} provides the
 * access-control layer; this endpoint just streams the bytes.
 */
class FilesController extends BaseStoreController
{
    public function get($productId, $filename)
    {
        $productId = (int) $productId;
        $filename  = basename((string) $filename); // strip path traversal
        $path = WRITEPATH . "uploads/product_files/{$productId}/{$filename}";

        if (! is_file($path)) return $this->response->setStatusCode(404)->setBody('Not found');

        $mime = mime_content_type($path) ?: 'application/octet-stream';
        return $this->response
            ->setHeader('Content-Type',        $mime)
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setHeader('Content-Length',      (string) filesize($path))
            ->setBody(file_get_contents($path));
    }
}
