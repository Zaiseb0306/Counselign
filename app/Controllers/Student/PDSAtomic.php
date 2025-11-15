<?php

namespace App\Controllers\Student;


use App\Helpers\SecureLogHelper;
use App\Controllers\BaseController;
use App\Models\StudentPDSModel;
use CodeIgniter\Exceptions\CodeIgniterException;

/**
 * Atomic PDS Controller
 * 
 * Demonstrates the usage of atomic PDS operations
 * for ACID compliance in the UGC Counseling System.
 */
class PDSAtomic extends BaseController
{
    private StudentPDSModel $pdsModel;

    public function __construct()
    {
        parent::__construct();
        $this->pdsModel = new StudentPDSModel();
    }

    /**
     * Save complete PDS data using atomic operations
     * 
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function saveCompletePDSAtomic()
    {
        try {
            // Check authentication
            if (!session()->get('logged_in') || session()->get('role') !== 'student') {
                return $this->response->setStatusCode(401)
                    ->setJSON([
                        'success' => false,
                        'message' => 'Unauthorized access'
                    ]);
            }

            $userId = session()->get('user_id_display');
            if (!$userId) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'User ID not found in session'
                ])->setStatusCode(400);
            }

            // Get PDS data from request
            $pdsData = $this->request->getPost();
            
            // Validate required sections
            $requiredSections = ['academic', 'personal'];
            foreach ($requiredSections as $section) {
                if (!isset($pdsData[$section]) || !is_array($pdsData[$section])) {
                    return $this->response->setJSON([
                        'success' => false,
                        'message' => "Required PDS section '{$section}' is missing or invalid"
                    ])->setStatusCode(400);
                }
            }

            // Save PDS data atomically
            $result = $this->pdsModel->saveCompletePDSAtomic($userId, $pdsData);

            return $this->response->setJSON($result);

        } catch (CodeIgniterException $e) {
            log_message('error', 'Atomic PDS save failed: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => $e->getMessage()
            ])->setStatusCode(400);
        } catch (\Exception $e) {
            log_message('error', 'Unexpected error in atomic PDS save: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'An unexpected error occurred'
            ])->setStatusCode(500);
        }
    }

    /**
     * Update specific PDS section using atomic operations
     * 
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function updatePDSSectionAtomic()
    {
        try {
            // Check authentication
            if (!session()->get('logged_in') || session()->get('role') !== 'student') {
                return $this->response->setStatusCode(401)
                    ->setJSON([
                        'success' => false,
                        'message' => 'Unauthorized access'
                    ]);
            }

            $userId = session()->get('user_id_display');
            $section = $this->request->getPost('section');
            $sectionData = $this->request->getPost('section_data');

            if (!$userId || !$section || !$sectionData) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'User ID, section, and section data are required'
                ])->setStatusCode(400);
            }

            // Validate section name
            $validSections = [
                'academic', 'personal', 'address', 'family', 
                'circumstances', 'services_needed', 'services_availed', 'residence'
            ];

            if (!in_array($section, $validSections)) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Invalid PDS section'
                ])->setStatusCode(400);
            }

            // Update section atomically
            $result = $this->pdsModel->updatePDSSectionAtomic($userId, $section, $sectionData);

            return $this->response->setJSON($result);

        } catch (CodeIgniterException $e) {
            log_message('error', 'Atomic PDS section update failed: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => $e->getMessage()
            ])->setStatusCode(400);
        } catch (\Exception $e) {
            log_message('error', 'Unexpected error in atomic PDS section update: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'An unexpected error occurred'
            ])->setStatusCode(500);
        }
    }

    /**
     * Delete PDS data using atomic operations
     * 
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function deletePDSAtomic()
    {
        try {
            // Check authentication
            if (!session()->get('logged_in') || !in_array(session()->get('role'), ['student', 'admin'])) {
                return $this->response->setStatusCode(401)
                    ->setJSON([
                        'success' => false,
                        'message' => 'Unauthorized access'
                    ]);
            }

            $userId = session()->get('user_id_display');
            if (!$userId) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'User ID not found in session'
                ])->setStatusCode(400);
            }

            // Delete PDS data atomically
            $result = $this->pdsModel->deletePDSAtomic($userId);

            return $this->response->setJSON($result);

        } catch (CodeIgniterException $e) {
            log_message('error', 'Atomic PDS deletion failed: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => $e->getMessage()
            ])->setStatusCode(400);
        } catch (\Exception $e) {
            log_message('error', 'Unexpected error in atomic PDS deletion: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'An unexpected error occurred'
            ])->setStatusCode(500);
        }
    }

    /**
     * Get PDS completion status
     * 
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function getPDSStatus()
    {
        try {
            // Check authentication
            if (!session()->get('logged_in') || session()->get('role') !== 'student') {
                return $this->response->setStatusCode(401)
                    ->setJSON([
                        'success' => false,
                        'message' => 'Unauthorized access'
                    ]);
            }

            $userId = session()->get('user_id_display');
            if (!$userId) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'User ID not found in session'
                ])->setStatusCode(400);
            }

            $hasPDS = $this->pdsModel->hasPDS($userId);

            return $this->response->setJSON([
                'success' => true,
                'has_pds' => $hasPDS,
                'user_id' => $userId
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Failed to get PDS status: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to get PDS status'
            ])->setStatusCode(500);
        }
    }
}
