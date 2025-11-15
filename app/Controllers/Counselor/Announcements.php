<?php
namespace App\Controllers\Counselor;


use App\Helpers\SecureLogHelper;
use App\Controllers\BaseController;

class Announcements extends BaseController
{
    public function index()
    {
        // Check if user is logged in and is counselor
        if (!session()->get('logged_in') || session()->get('role') !== 'counselor') {
            return redirect()->to('/');
        }

        return view('counselor/counselor_announcements');
    }

    public function getAll()
    {
        // Check if user is logged in and is counselor
        if (!session()->get('logged_in') || session()->get('role') !== 'counselor') {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Unauthorized access'
            ])->setStatusCode(401);
        }

        $announcementModel = new \App\Models\AnnouncementModel();
        try {
            $announcements = $announcementModel->orderBy('created_at', 'DESC')->findAll();
            return $this->response->setJSON([
                'status' => 'success',
                'announcements' => $announcements
            ]);
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
}
