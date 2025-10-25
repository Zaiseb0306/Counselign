<?php

namespace App\Controllers\Admin;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;

class AnnouncementsApi extends \CodeIgniter\Controller
{
    use ResponseTrait;

    public function index()
    {
        // GET: List all announcements
        $db = \Config\Database::connect();
        $builder = $db->table('announcements');
        $builder->orderBy('created_at', 'DESC');
        $query = $builder->get();
        $announcements = $query->getResultArray();
        return $this->respond(['success' => true, 'data' => $announcements]);
    }

    public function create()
    {
        // POST: Create new announcement
        $input = $this->request->getJSON(true);
        $title = trim($input['title'] ?? '');
        $content = trim($input['content'] ?? '');
        if ($title === '' || $content === '') {
            return $this->respond(['success' => false, 'message' => 'Title and content required'], 400);
        }
        $db = \Config\Database::connect();
        $builder = $db->table('announcements');
        $builder->insert(['title' => $title, 'content' => $content]);
        return $this->respond(['success' => true, 'id' => $db->insertID()]);
    }

    public function update($id = null)
    {
        // PUT: Update announcement
        $input = $this->request->getJSON(true);
        $id = $id ?? intval($input['id'] ?? 0);
        $title = trim($input['title'] ?? '');
        $content = trim($input['content'] ?? '');
        if ($id <= 0 || $title === '' || $content === '') {
            return $this->respond(['success' => false, 'message' => 'ID, title, and content required'], 400);
        }
        $db = \Config\Database::connect();
        $builder = $db->table('announcements');
        $builder->where('id', $id)->update(['title' => $title, 'content' => $content]);
        return $this->respond(['success' => true]);
    }

    public function delete($id = null)
    {
        // DELETE: Remove announcement by id
        $id = intval($id ?? 0);
        if ($id <= 0) {
            return $this->respond(['success' => false, 'message' => 'Valid ID required'], 400);
        }

        $db = \Config\Database::connect();
        $builder = $db->table('announcements');
        $builder->where('id', $id)->delete();

        if ($db->affectedRows() > 0) {
            return $this->respond(['success' => true]);
        }

        return $this->respond(['success' => false, 'message' => 'Announcement not found'], 404);
    }
}