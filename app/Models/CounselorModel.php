<?php

namespace App\Models;


use App\Helpers\SecureLogHelper;
use CodeIgniter\Model;

/**
 * Counselor Model
 * 
 * Handles all database operations for counselors table.
 * Note: counselor_id is linked to users.user_id via foreign key.
 * Availability is now managed separately in CounselorAvailabilityModel.
 */
class CounselorModel extends Model
{
    protected $table = 'counselors';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'counselor_id',
        'name',
        'degree',
        'email',
        'contact_number',
        'address',
        'profile_picture',
        'civil_status',
        'sex',
        'birthdate'
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $returnType = 'array';
    protected $useSoftDeletes = false;

    /**
     * Validation Rules
     */
    protected $validationRules = [
        'counselor_id' => 'required|max_length[20]|is_unique[counselors.counselor_id,id,{id}]',
        'name' => 'required|max_length[100]',
        'degree' => 'required|max_length[100]',
        'email' => 'required|valid_email|max_length[100]|is_unique[counselors.email,id,{id}]',
        'contact_number' => 'required|max_length[20]|regex_match[/^09[0-9]{9}$/]',
        'address' => 'required',
        'profile_picture' => 'permit_empty|max_length[255]',
        'civil_status' => 'permit_empty|max_length[20]|in_list[Single,Married,Widowed,Legally Separated,Annulled]',
        'sex' => 'permit_empty|max_length[10]|in_list[Male,Female]',
        'birthdate' => 'permit_empty|valid_date[Y-m-d]'
    ];

    /**
     * Validation Messages
     */
    protected $validationMessages = [
        'counselor_id' => [
            'required' => 'Counselor ID is required',
            'is_unique' => 'This Counselor ID already exists'
        ],
        'name' => [
            'required' => 'Counselor name is required',
            'max_length' => 'Name cannot exceed 100 characters'
        ],
        'degree' => [
            'required' => 'Degree is required',
            'max_length' => 'Degree cannot exceed 100 characters'
        ],
        'email' => [
            'required' => 'Email is required',
            'valid_email' => 'Please enter a valid email address',
            'is_unique' => 'This email is already registered'
        ],
        'contact_number' => [
            'required' => 'Contact number is required',
            'regex_match' => 'Contact number must be in format 09XXXXXXXXX'
        ],
        'address' => [
            'required' => 'Address is required'
        ],
        'civil_status' => [
            'in_list' => 'Invalid civil status selected'
        ],
        'sex' => [
            'in_list' => 'Invalid sex selected'
        ],
        'birthdate' => [
            'valid_date' => 'Invalid date format. Use YYYY-MM-DD'
        ]
    ];

    /**
     * Get counselor by counselor_id (not primary key)
     * 
     * @param string $counselorId
     * @return array|null
     */
    public function getByCounselorId(string $counselorId): ?array
    {
        return $this->where('counselor_id', $counselorId)->first();
    }

    /**
     * Get counselor by email
     * 
     * @param string $email
     * @return array|null
     */
    public function getByEmail(string $email): ?array
    {
        return $this->where('email', $email)->first();
    }

    /**
     * Get all active counselors
     * 
     * @return array
     */
    public function getActiveCounselors(): array
    {
        return $this->orderBy('name', 'ASC')->findAll();
    }

    /**
     * Get counselor with user account details (JOIN)
     * Profile picture is retrieved from users table, not counselors table
     * 
     * @param string $counselorId
     * @return array|null
     */
    public function getCounselorWithUser(string $counselorId): ?array
    {
        return $this->select('counselors.counselor_id, counselors.name, counselors.degree, 
                             counselors.email, counselors.contact_number, counselors.address,
                             counselors.civil_status, counselors.sex, counselors.birthdate,
                             users.username, users.last_login, users.is_verified, users.profile_picture')
                    ->join('users', 'counselors.counselor_id = users.user_id', 'left')
                    ->where('counselors.counselor_id', $counselorId)
                    ->first();
    }

    /**
     * Get all counselors with their user account info
     * Profile picture is retrieved from users table, not counselors table
     * 
     * @return array
     */
    public function getAllCounselorsWithUsers(): array
    {
        return $this->select('counselors.counselor_id, counselors.name, counselors.degree, 
                             counselors.email, counselors.contact_number, counselors.address,
                             counselors.civil_status, counselors.sex, counselors.birthdate,
                             users.username, users.last_login, users.is_verified, users.profile_picture')
                    ->join('users', 'counselors.counselor_id = users.user_id', 'left')
                    ->orderBy('counselors.name', 'ASC')
                    ->findAll();
    }

    /**
     * Get counselor with availability (JOIN)
     * 
     * @param string $counselorId
     * @return array|null
     */
    public function getCounselorWithAvailability(string $counselorId): ?array
    {
        $counselor = $this->getByCounselorId($counselorId);
        
        if (!$counselor) {
            return null;
        }

        // Get availability from separate model
        $availabilityModel = new CounselorAvailabilityModel();
        $counselor['availability'] = $availabilityModel->getGroupedByDay($counselorId);

        return $counselor;
    }

    /**
     * Get all counselors with their availability
     * 
     * @return array
     */
    public function getAllWithAvailability(): array
    {
        $counselors = $this->getActiveCounselors();
        $availabilityModel = new CounselorAvailabilityModel();

        foreach ($counselors as &$counselor) {
            $counselor['availability'] = $availabilityModel->getGroupedByDay($counselor['counselor_id']);
        }

        return $counselors;
    }

    /**
     * Get counselors available on specific day
     * 
     * @param string $day
     * @return array
     */
    public function getAvailableOnDay(string $day): array
    {
        $availabilityModel = new CounselorAvailabilityModel();
        $availableCounselorIds = $availabilityModel->getCounselorsAvailableOnDay($day);
        
        if (empty($availableCounselorIds)) {
            return [];
        }

        $ids = array_column($availableCounselorIds, 'counselor_id');
        
        return $this->whereIn('counselor_id', $ids)->findAll();
    }

    /**
     * Update or create counselor profile
     * 
     * @param string $counselorId
     * @param array $data
     * @return bool|int
     */
    public function upsert(string $counselorId, array $data)
    {
        $existing = $this->getByCounselorId($counselorId);
        
        if ($existing) {
            return $this->update($existing['id'], $data);
        } else {
            $data['counselor_id'] = $counselorId;
            return $this->insert($data);
        }
    }

    /**
     * Update profile picture
     * 
     * @param string $counselorId
     * @param string $filename
     * @return bool
     */
    public function updateProfilePicture(string $counselorId, string $filename): bool
    {
        $counselor = $this->getByCounselorId($counselorId);
        
        if (!$counselor) {
            return false;
        }
        
        return $this->update($counselor['id'], ['profile_picture' => $filename]);
    }

    /**
     * Search counselors by name, degree, or email
     * 
     * @param string $searchTerm
     * @return array
     */
    public function search(string $searchTerm): array
    {
        return $this->groupStart()
                    ->like('name', $searchTerm)
                    ->orLike('degree', $searchTerm)
                    ->orLike('email', $searchTerm)
                    ->groupEnd()
                    ->findAll();
    }

    /**
     * Get counselor statistics
     * 
     * @param string $counselorId
     * @return array
     */
    public function getStatistics(string $counselorId): array
    {
        $db = \Config\Database::connect();
        
        // Get appointment counts
        $appointmentModel = new \App\Models\AppointmentModel();
        $availabilityModel = new CounselorAvailabilityModel();
        
        return [
            'total_appointments' => $appointmentModel->where('counselor_preference', $counselorId)->countAllResults(),
            'pending_appointments' => $appointmentModel->where('counselor_preference', $counselorId)
                                                       ->where('status', 'pending')
                                                       ->countAllResults(),
            'completed_appointments' => $appointmentModel->where('counselor_preference', $counselorId)
                                                         ->where('status', 'completed')
                                                         ->countAllResults(),
            'availability_slots' => $availabilityModel->countSlots($counselorId)
        ];
    }

    /**
     * Check if counselor exists
     * 
     * @param string $counselorId
     * @return bool
     */
    public function exists(string $counselorId): bool
    {
        return $this->where('counselor_id', $counselorId)->countAllResults() > 0;
    }

    /**
     * Get counselor's full name with degree
     * 
     * @param string $counselorId
     * @return string
     */
    public function getFullNameWithDegree(string $counselorId): string
    {
        $counselor = $this->getByCounselorId($counselorId);
        
        if (!$counselor) {
            return 'Unknown Counselor';
        }
        
        return $counselor['name'] . ', ' . $counselor['degree'];
    }

    /**
     * Delete counselor (also deletes availability due to CASCADE)
     * 
     * @param string $counselorId
     * @return bool
     */
    public function deleteByCounselorId(string $counselorId): bool
    {
        $counselor = $this->getByCounselorId($counselorId);
        
        if (!$counselor) {
            return false;
        }
        
        // CASCADE will handle counselor_availability deletion
        return $this->delete($counselor['id']);
    }
}