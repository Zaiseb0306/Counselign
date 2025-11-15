<?php

namespace App\Controllers\Counselor;


use App\Helpers\SecureLogHelper;
use CodeIgniter\Controller;
use CodeIgniter\HTTP\ResponseInterface;

class Events extends Controller
{
    public function getAll()
    {
        // Check if user is logged in and is counselor
        if (!session()->get('logged_in') || session()->get('role') !== 'counselor') {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Unauthorized access'
            ])->setStatusCode(401);
        }

        $db = \Config\Database::connect();
        
        try {
            $query = $db->query('SELECT * FROM events ORDER BY date DESC, time DESC');
            $events = $query->getResultArray();
            
            return $this->response->setJSON([
                'status' => 'success',
                'events' => $events
            ]);
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => $e->getMessage()
            ])->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
