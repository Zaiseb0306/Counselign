<?php

namespace App\Controllers\Admin;

use CodeIgniter\API\ResponseTrait;

class EventsApi extends \CodeIgniter\Controller
{
    use ResponseTrait;

    public function index()
    {
        // GET: List all events
        $db = \Config\Database::connect();
        $builder = $db->table('events');
        $builder->orderBy('date', 'DESC')->orderBy('time', 'DESC');
        $query = $builder->get();
        $events = $query->getResultArray();
        return $this->respond(['success' => true, 'data' => $events]);
    }

    public function create()
    {
        // POST: Create new event
        $input = $this->request->getJSON(true);
        $title = trim($input['title'] ?? '');
        $description = trim($input['description'] ?? '');
        $date = trim($input['date'] ?? '');
        $time = trim($input['time'] ?? '');
        $location = trim($input['location'] ?? '');

        if ($title && $description && $date && $time && $location) {
            $db = \Config\Database::connect();
            $builder = $db->table('events');
            $builder->insert([
                'title' => $title,
                'description' => $description,
                'date' => $date,
                'time' => $time,
                'location' => $location
            ]);
            return $this->respond([
                'success' => true,
                'message' => 'Event added successfully.',
                'event_id' => $db->insertID()
            ]);
        } else {
            return $this->respond([
                'success' => false,
                'message' => 'All fields are required.'
            ], 400);
        }
    }

    public function update($id = null)
    {
        // PUT: Update event
        $input = $this->request->getJSON(true);
        $id = $id ?? ($input['id'] ?? null);
        $title = trim($input['title'] ?? '');
        $description = trim($input['description'] ?? '');
        $date = trim($input['date'] ?? '');
        $time = trim($input['time'] ?? '');
        $location = trim($input['location'] ?? '');

        if ($id && $title && $description && $date && $time && $location) {
            $db = \Config\Database::connect();
            $builder = $db->table('events');
            $builder->where('id', $id)->update([
                'title' => $title,
                'description' => $description,
                'date' => $date,
                'time' => $time,
                'location' => $location
            ]);
            if ($db->affectedRows() > 0) {
                return $this->respond([
                    'success' => true,
                    'message' => 'Event updated successfully.'
                ]);
            } else {
                return $this->respond([
                    'success' => false,
                    'message' => 'Event not found or no changes made.'
                ], 404);
            }
        } else {
            return $this->respond([
                'success' => false,
                'message' => 'All fields are required.'
            ], 400);
        }
    }

    public function delete($id = null)
    {
        // DELETE: Remove event by id
        $id = intval($id ?? 0);
        if ($id <= 0) {
            return $this->respond([
                'success' => false,
                'message' => 'Valid ID required.'
            ], 400);
        }

        $db = \Config\Database::connect();
        $builder = $db->table('events');
        $builder->where('id', $id)->delete();

        if ($db->affectedRows() > 0) {
            return $this->respond([
                'success' => true,
                'message' => 'Event deleted successfully.'
            ]);
        }

        return $this->respond([
            'success' => false,
            'message' => 'Event not found.'
        ], 404);
    }
}
