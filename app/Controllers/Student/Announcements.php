<?php
namespace App\Controllers\Student;


use App\Helpers\SecureLogHelper;
use App\Controllers\BaseController;

class Announcements extends BaseController
{
    public function index()
    {
        // Check if user is logged in and is a student
        if (!session()->get('logged_in') || session()->get('role') !== 'student') {
            return redirect()->to('/');
        }

        return view('student/student_announcements');
    }

    public function getAll()
    {
        // Check if user is logged in and is a student
        if (!session()->get('logged_in') || session()->get('role') !== 'student') {
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
