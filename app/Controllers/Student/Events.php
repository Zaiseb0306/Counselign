<?php

namespace App\Controllers\Student;


use App\Helpers\SecureLogHelper;
use CodeIgniter\Controller;
use CodeIgniter\HTTP\ResponseInterface;

class Events extends Controller
{
    public function getAll()
    {
        // Check if user is logged in and is a student
        if (!session()->get('logged_in') || session()->get('role') !== 'student') {
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