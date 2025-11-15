<?php

namespace App\Controllers\Student;


use App\Helpers\SecureLogHelper;
use App\Controllers\BaseController;
use App\Models\AppointmentModel;
use CodeIgniter\Exceptions\CodeIgniterException;

/**
 * Atomic Appointment Controller
 * 
 * Demonstrates the usage of atomic appointment operations
 * for ACID compliance in the UGC Counseling System.
 */
class AppointmentAtomic extends BaseController
{
    private AppointmentModel $appointmentModel;

    public function __construct()
    {
        parent::__construct();
        $this->appointmentModel = new AppointmentModel();
    }

    /**
     * Create appointment using atomic operations
     * 
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function createAppointmentAtomic()
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

            // Get request data
            $appointmentData = [
                'student_id' => session()->get('user_id_display'),
                'preferred_date' => $this->request->getPost('preferred_date'),
                'preferred_time' => $this->request->getPost('preferred_time'),
                'consultation_type' => $this->request->getPost('consultation_type'),
                'counselor_preference' => $this->request->getPost('counselor_preference') ?? 'No preference',
                'description' => $this->request->getPost('description'),
                'reason' => $this->request->getPost('reason'),
                'purpose' => $this->request->getPost('purpose')
            ];

            // Validate required fields
            $requiredFields = ['preferred_date', 'preferred_time', 'consultation_type', 'purpose'];
            foreach ($requiredFields as $field) {
                if (empty($appointmentData[$field])) {
                    return $this->response->setJSON([
                        'success' => false,
                        'message' => "Field '{$field}' is required"
                    ])->setStatusCode(400);
                }
            }

            // Create appointment atomically
            $result = $this->appointmentModel->createAppointmentAtomic($appointmentData);

            return $this->response->setJSON($result);

        } catch (CodeIgniterException $e) {
            log_message('error', 'Atomic appointment creation failed: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => $e->getMessage()
            ])->setStatusCode(400);
        } catch (\Exception $e) {
            log_message('error', 'Unexpected error in atomic appointment creation: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'An unexpected error occurred'
            ])->setStatusCode(500);
        }
    }

    /**
     * Update appointment status using atomic operations
     * 
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function updateStatusAtomic()
    {
        try {
            // Check authentication
            if (!session()->get('logged_in') || !in_array(session()->get('role'), ['admin', 'counselor'])) {
                return $this->response->setStatusCode(401)
                    ->setJSON([
                        'success' => false,
                        'message' => 'Unauthorized access'
                    ]);
            }

            $appointmentId = (int) $this->request->getPost('appointment_id');
            $newStatus = $this->request->getPost('status');
            $reason = $this->request->getPost('reason');

            if (!$appointmentId || !$newStatus) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Appointment ID and status are required'
                ])->setStatusCode(400);
            }

            // Update status atomically
            $result = $this->appointmentModel->updateStatusAtomic($appointmentId, $newStatus, $reason);

            return $this->response->setJSON($result);

        } catch (CodeIgniterException $e) {
            log_message('error', 'Atomic status update failed: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => $e->getMessage()
            ])->setStatusCode(400);
        } catch (\Exception $e) {
            log_message('error', 'Unexpected error in atomic status update: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'An unexpected error occurred'
            ])->setStatusCode(500);
        }
    }

    /**
     * Cancel appointment using atomic operations
     * 
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function cancelAppointmentAtomic()
    {
        try {
            // Check authentication
            if (!session()->get('logged_in') || !in_array(session()->get('role'), ['student', 'admin', 'counselor'])) {
                return $this->response->setStatusCode(401)
                    ->setJSON([
                        'success' => false,
                        'message' => 'Unauthorized access'
                    ]);
            }

            $appointmentId = (int) $this->request->getPost('appointment_id');
            $reason = $this->request->getPost('reason');

            if (!$appointmentId || !$reason) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Appointment ID and cancellation reason are required'
                ])->setStatusCode(400);
            }

            // Cancel appointment atomically
            $result = $this->appointmentModel->cancelAppointmentAtomic($appointmentId, $reason);

            return $this->response->setJSON($result);

        } catch (CodeIgniterException $e) {
            log_message('error', 'Atomic appointment cancellation failed: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => $e->getMessage()
            ])->setStatusCode(400);
        } catch (\Exception $e) {
            log_message('error', 'Unexpected error in atomic appointment cancellation: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'An unexpected error occurred'
            ])->setStatusCode(500);
        }
    }

    /**
     * Get transaction status for debugging
     * 
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function getTransactionStatus()
    {
        try {
            // Check authentication
            if (!session()->get('logged_in') || session()->get('role') !== 'admin') {
                return $this->response->setStatusCode(401)
                    ->setJSON([
                        'success' => false,
                        'message' => 'Unauthorized access'
                    ]);
            }

            $status = $this->appointmentModel->getTransactionStatus();

            return $this->response->setJSON([
                'success' => true,
                'transaction_status' => $status
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Failed to get transaction status: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to get transaction status'
            ])->setStatusCode(500);
        }
    }
}
