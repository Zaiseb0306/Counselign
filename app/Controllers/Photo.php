<?php

namespace App\Controllers;


use App\Helpers\SecureLogHelper;
use CodeIgniter\HTTP\Response;

class Photo extends BaseController
{
    public function show($type = null, $filename = null)
    {
        $allowedTypes = ['counselor_profiles', 'profile.png', 'UGC-Logo.png', 'favicon.ico'];
        if ($type === 'profile.png' || $type === 'UGC-Logo.png' || $type === 'favicon.ico') {
            $filePath = ROOTPATH . "Photos/$type";
        } else if (in_array($type, ['counselor_profiles'])) {
            $filePath = FCPATH . "Photos/$type/$filename";
        } else {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        if (!is_file($filePath)) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $mime = mime_content_type($filePath);
        return $this->response->setHeader('Content-Type', $mime)->setBody(file_get_contents($filePath));
    }
}
