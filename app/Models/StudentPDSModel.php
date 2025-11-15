<?php

namespace App\Models;

use App\Helpers\SecureLogHelper;
use CodeIgniter\Model;

/**
 * Complete PDS Model - Aggregates all PDS data with ACID compliance
 * UPDATED: Added support for Other Info, GCS Activities, and Awards
 */
class StudentPDSModel extends BaseModel
{
    protected $academicModel;
    protected $personalModel;
    protected $addressModel;
    protected $familyModel;
    protected $circumstancesModel;
    protected $servicesNeededModel;
    protected $servicesAvailedModel;
    protected $residenceModel;
    protected $otherInfoModel;
    protected $gcsActivitiesModel;
    protected $awardsModel;

    public function __construct()
    {
        parent::__construct();

        $this->academicModel = new StudentAcademicInfoModel();
        $this->personalModel = new StudentPersonalInfoModel();
        $this->addressModel = new StudentAddressInfoModel();
        $this->familyModel = new StudentFamilyInfoModel();
        $this->circumstancesModel = new StudentSpecialCircumstancesModel();
        $this->servicesNeededModel = new StudentServicesNeededModel();
        $this->servicesAvailedModel = new StudentServicesAvailedModel();
        $this->residenceModel = new StudentResidenceInfoModel();
        $this->otherInfoModel = new StudentOtherInfoModel();
        $this->gcsActivitiesModel = new StudentGCSActivitiesModel();
        $this->awardsModel = new StudentAwardsModel();
    }

    /**
     * Get complete PDS for a user
     */
    public function getCompletePDS(string $userId): array
    {
        return [
            'academic' => $this->academicModel->getByUserId($userId),
            'personal' => $this->personalModel->getByUserId($userId),
            'address' => $this->addressModel->getByUserId($userId),
            'family' => $this->familyModel->getByUserId($userId),
            'circumstances' => $this->circumstancesModel->getByUserId($userId),
            'services_needed' => $this->servicesNeededModel->getServicesArray($userId),
            'services_availed' => $this->servicesAvailedModel->getServicesArray($userId),
            'residence' => $this->residenceModel->getByUserId($userId),
            'other_info' => $this->otherInfoModel->getByUserId($userId),
            'gcs_activities' => $this->gcsActivitiesModel->getActivitiesArray($userId),
            'awards' => $this->awardsModel->getAwardsArray($userId)
        ];
    }

    /**
     * Save complete PDS data
     */
    public function saveCompletePDS(string $userId, array $pdsData): bool
    {
        $userId = (string) $userId;
        $this->db->transStart();

        try {
            // Save each section (existing code remains the same)
            if (isset($pdsData['academic'])) {
                log_message('debug', 'Saving academic data...');
                $this->academicModel->upsert($userId, $pdsData['academic']);
            }

            if (isset($pdsData['personal'])) {
                log_message('debug', 'Saving personal data...');
                $result = $this->personalModel->upsert($userId, $pdsData['personal']);
                log_message('debug', 'Personal data upsert result: ' . ($result ? 'SUCCESS' : 'FAILED'));
            }

            if (isset($pdsData['address'])) {
                $this->addressModel->upsert($userId, $pdsData['address']);
            }

            if (isset($pdsData['family'])) {
                $this->familyModel->upsert($userId, $pdsData['family']);
            }

            if (isset($pdsData['circumstances'])) {
                $this->circumstancesModel->upsert($userId, $pdsData['circumstances']);
            }

            if (isset($pdsData['services_needed'])) {
                $this->servicesNeededModel->syncServices($userId, $pdsData['services_needed']);
            }

            if (isset($pdsData['services_availed'])) {
                $this->servicesAvailedModel->syncServices($userId, $pdsData['services_availed']);
            }

            if (isset($pdsData['residence'])) {
                $this->residenceModel->upsert($userId, $pdsData['residence']);
            }

            // NEW SECTIONS
            if (isset($pdsData['other_info'])) {
                log_message('debug', 'Saving other info data...');
                $this->otherInfoModel->upsert($userId, $pdsData['other_info']);
            }

            if (isset($pdsData['gcs_activities'])) {
                log_message('debug', 'Syncing GCS activities...');
                $this->gcsActivitiesModel->syncActivities($userId, $pdsData['gcs_activities']);
            }

            if (isset($pdsData['awards'])) {
                log_message('debug', 'Syncing awards...');
                $this->awardsModel->syncAwards($userId, $pdsData['awards']);
            }

            $this->db->transComplete();
            return $this->db->transStatus();
        } catch (\Exception $e) {
            $this->db->transRollback();
            log_message('error', 'PDS Save Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if user has completed PDS
     */
    public function hasPDS(string $userId): bool
    {
        return $this->academicModel->getByUserId($userId) !== null;
    }

    // ========================================
    // ATOMIC PDS OPERATIONS FOR ACID COMPLIANCE
    // ========================================

    /**
     * Save complete PDS data atomically with comprehensive validation
     * 
     * @param string $userId Student ID
     * @param array $pdsData Complete PDS data
     * @param array $options Transaction options
     * @return array Result with success status and details
     * @throws \Exception On validation or transaction failure
     */
    public function saveCompletePDSAtomic(string $userId, array $pdsData, array $options = []): array
    {
        // Validate user ID format
        if (!preg_match('/^\d{10}$/', $userId)) {
            throw new \Exception('Invalid student ID format. Must be exactly 10 digits.');
        }

        // Validate PDS data structure
        $this->validatePDSDataStructure($pdsData);

        // Prepare atomic operations
        $operations = $this->preparePDSOperations($userId, $pdsData);

        try {
            $results = $this->executeWithLocking(
                $operations,
                [
                    'student_academic_info',
                    'student_personal_info',
                    'student_address_info',
                    'student_family_info',
                    'student_special_circumstances',
                    'student_services_needed',
                    'student_services_availed',
                    'student_residence_info'
                ],
                $options
            );

            $this->logAtomicOperation('saveCompletePDS', [
                'user_id' => $userId,
                'sections_saved' => count($pdsData)
            ], true);

            return [
                'success' => true,
                'user_id' => $userId,
                'sections_saved' => array_keys($pdsData),
                'message' => 'PDS data saved successfully'
            ];
        } catch (\Exception $e) {
            $this->logAtomicOperation('saveCompletePDS', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ], false);
            throw new \Exception('Failed to save PDS data: ' . $e->getMessage());
        }
    }

    /**
     * Update specific PDS section atomically
     * 
     * @param string $userId Student ID
     * @param string $section PDS section name
     * @param array $sectionData Section data
     * @param array $options Transaction options
     * @return array Result of section update
     * @throws \Exception On validation or transaction failure
     */
    public function updatePDSSectionAtomic(string $userId, string $section, array $sectionData, array $options = []): array
    {
        // Validate section name
        $validSections = [
            'academic',
            'personal',
            'address',
            'family',
            'circumstances',
            'services_needed',
            'services_availed',
            'residence'
        ];

        if (!in_array($section, $validSections)) {
            throw new \Exception('Invalid PDS section: ' . $section);
        }

        // Validate section-specific data
        $this->validatePDSSectionData($section, $sectionData);

        // Prepare atomic operations for specific section
        $operations = $this->preparePDSSectionOperations($userId, $section, $sectionData);

        try {
            $results = $this->executeWithLocking(
                $operations,
                [$this->getTableNameForSection($section)],
                $options
            );

            $this->logAtomicOperation('updatePDSSection', [
                'user_id' => $userId,
                'section' => $section
            ], true);

            return [
                'success' => true,
                'user_id' => $userId,
                'section' => $section,
                'message' => ucfirst($section) . ' section updated successfully'
            ];
        } catch (\Exception $e) {
            $this->logAtomicOperation('updatePDSSection', [
                'user_id' => $userId,
                'section' => $section,
                'error' => $e->getMessage()
            ], false);
            throw new \Exception('Failed to update PDS section: ' . $e->getMessage());
        }
    }

    /**
     * Delete PDS data atomically
     * 
     * @param string $userId Student ID
     * @param array $options Transaction options
     * @return array Result of deletion
     * @throws \Exception On transaction failure
     */
    public function deletePDSAtomic(string $userId, array $options = []): array
    {
        // Prepare atomic operations to delete all PDS data
        $operations = [
            $this->createAtomicOperation('deleteAcademicInfo', [$userId], $this->academicModel),
            $this->createAtomicOperation('deletePersonalInfo', [$userId], $this->personalModel),
            $this->createAtomicOperation('deleteAddressInfo', [$userId], $this->addressModel),
            $this->createAtomicOperation('deleteFamilyInfo', [$userId], $this->familyModel),
            $this->createAtomicOperation('deleteSpecialCircumstances', [$userId], $this->circumstancesModel),
            $this->createAtomicOperation('deleteServicesNeeded', [$userId], $this->servicesNeededModel),
            $this->createAtomicOperation('deleteServicesAvailed', [$userId], $this->servicesAvailedModel),
            $this->createAtomicOperation('deleteResidenceInfo', [$userId], $this->residenceModel)
        ];

        try {
            $results = $this->executeWithLocking(
                $operations,
                [
                    'student_academic_info',
                    'student_personal_info',
                    'student_address_info',
                    'student_family_info',
                    'student_special_circumstances',
                    'student_services_needed',
                    'student_services_availed',
                    'student_residence_info'
                ],
                $options
            );

            $this->logAtomicOperation('deletePDS', ['user_id' => $userId], true);

            return [
                'success' => true,
                'user_id' => $userId,
                'message' => 'PDS data deleted successfully'
            ];
        } catch (\Exception $e) {
            $this->logAtomicOperation('deletePDS', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ], false);
            throw new \Exception('Failed to delete PDS data: ' . $e->getMessage());
        }
    }

    // ========================================
    // ATOMIC OPERATION HELPER METHODS
    // ========================================

    /**
     * Validate PDS data structure
     * 
     * @param array $pdsData
     * @return bool
     * @throws \Exception
     */
    private function validatePDSDataStructure(array $pdsData): bool
    {
        $requiredSections = ['academic', 'personal'];
        $optionalSections = ['address', 'family', 'circumstances', 'services_needed', 'services_availed', 'residence'];

        // Check required sections
        foreach ($requiredSections as $section) {
            if (!isset($pdsData[$section]) || !is_array($pdsData[$section])) {
                throw new \Exception("Required PDS section '{$section}' is missing or invalid");
            }
        }

        // Validate each section
        foreach ($pdsData as $section => $data) {
            if (!is_array($data)) {
                throw new \Exception("PDS section '{$section}' must be an array");
            }
            $this->validatePDSSectionData($section, $data);
        }

        return true;
    }

    /**
     * Validate specific PDS section data
     * 
     * @param string $section
     * @param array $data
     * @return bool
     * @throws \Exception
     */
    private function validatePDSSectionData(string $section, array $data): bool
    {
        switch ($section) {
            case 'academic':
                $this->validateAcademicData($data);
                break;
            case 'personal':
                $this->validatePersonalData($data);
                break;
            case 'address':
                $this->validateAddressData($data);
                break;
            case 'family':
                $this->validateFamilyData($data);
                break;
            case 'circumstances':
                $this->validateCircumstancesData($data);
                break;
            case 'services_needed':
            case 'services_availed':
                $this->validateServicesData($data);
                break;
            case 'residence':
                $this->validateResidenceData($data);
                break;
            default:
                throw new \Exception("Unknown PDS section: {$section}");
        }

        return true;
    }

    /**
     * Prepare atomic operations for complete PDS save
     * 
     * @param string $userId
     * @param array $pdsData
     * @return array
     */
    private function preparePDSOperations(string $userId, array $pdsData): array
    {
        $operations = [];

        // Academic info
        if (isset($pdsData['academic'])) {
            $operations[] = $this->createAtomicOperation('upsert', [$userId, $pdsData['academic']], $this->academicModel);
        }

        // Personal info
        if (isset($pdsData['personal'])) {
            $operations[] = $this->createAtomicOperation('upsert', [$userId, $pdsData['personal']], $this->personalModel);
        }

        // Address info
        if (isset($pdsData['address'])) {
            $operations[] = $this->createAtomicOperation('upsert', [$userId, $pdsData['address']], $this->addressModel);
        }

        // Family info
        if (isset($pdsData['family'])) {
            $operations[] = $this->createAtomicOperation('upsert', [$userId, $pdsData['family']], $this->familyModel);
        }

        // Special circumstances
        if (isset($pdsData['circumstances'])) {
            $operations[] = $this->createAtomicOperation('upsert', [$userId, $pdsData['circumstances']], $this->circumstancesModel);
        }

        // Services needed
        if (isset($pdsData['services_needed'])) {
            $operations[] = $this->createAtomicOperation('syncServices', [$userId, $pdsData['services_needed']], $this->servicesNeededModel);
        }

        // Services availed
        if (isset($pdsData['services_availed'])) {
            $operations[] = $this->createAtomicOperation('syncServices', [$userId, $pdsData['services_availed']], $this->servicesAvailedModel);
        }

        // Residence info
        if (isset($pdsData['residence'])) {
            $operations[] = $this->createAtomicOperation('upsert', [$userId, $pdsData['residence']], $this->residenceModel);
        }

        return $operations;
    }

    /**
     * Prepare atomic operations for specific PDS section
     * 
     * @param string $userId
     * @param string $section
     * @param array $sectionData
     * @return array
     */
    private function preparePDSSectionOperations(string $userId, string $section, array $sectionData): array
    {
        $model = $this->getModelForSection($section);

        if (in_array($section, ['services_needed', 'services_availed'])) {
            return [$this->createAtomicOperation('syncServices', [$userId, $sectionData], $model)];
        } else {
            return [$this->createAtomicOperation('upsert', [$userId, $sectionData], $model)];
        }
    }

    /**
     * Get model instance for PDS section
     * 
     * @param string $section
     * @return mixed
     * @throws \Exception
     */
    private function getModelForSection(string $section)
    {
        switch ($section) {
            case 'academic':
                return $this->academicModel;
            case 'personal':
                return $this->personalModel;
            case 'address':
                return $this->addressModel;
            case 'family':
                return $this->familyModel;
            case 'circumstances':
                return $this->circumstancesModel;
            case 'services_needed':
                return $this->servicesNeededModel;
            case 'services_availed':
                return $this->servicesAvailedModel;
            case 'residence':
                return $this->residenceModel;
            default:
                throw new \Exception("Unknown PDS section: {$section}");
        }
    }

    /**
     * Get table name for PDS section
     * 
     * @param string $section
     * @return string
     * @throws \Exception
     */
    private function getTableNameForSection(string $section): string
    {
        switch ($section) {
            case 'academic':
                return 'student_academic_info';
            case 'personal':
                return 'student_personal_info';
            case 'address':
                return 'student_address_info';
            case 'family':
                return 'student_family_info';
            case 'circumstances':
                return 'student_special_circumstances';
            case 'services_needed':
                return 'student_services_needed';
            case 'services_availed':
                return 'student_services_availed';
            case 'residence':
                return 'student_residence_info';
            default:
                throw new \Exception("Unknown PDS section: {$section}");
        }
    }

    // ========================================
    // SECTION-SPECIFIC VALIDATION METHODS
    // ========================================

    private function validateAcademicData(array $data): void
    {
        $required = ['course', 'year_level', 'academic_status'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new \Exception("Academic info field '{$field}' is required");
            }
        }
    }

    private function validatePersonalData(array $data): void
    {
        $required = ['last_name', 'first_name', 'date_of_birth', 'sex'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new \Exception("Personal info field '{$field}' is required");
            }
        }

        // Validate date format
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['date_of_birth'])) {
            throw new \Exception('Invalid date format for date_of_birth');
        }

        // Validate sex enum
        if (!in_array($data['sex'], ['Male', 'Female'])) {
            throw new \Exception('Invalid sex value. Must be Male or Female');
        }
    }

    private function validateAddressData(array $data): void
    {
        // Address data is optional, but if provided, validate format
        if (isset($data['permanent_city']) && !is_string($data['permanent_city'])) {
            throw new \Exception('Permanent city must be a string');
        }
    }

    private function validateFamilyData(array $data): void
    {
        // Family data is optional, but validate format if provided
        if (isset($data['father_name']) && !is_string($data['father_name'])) {
            throw new \Exception('Father name must be a string');
        }
    }

    private function validateCircumstancesData(array $data): void
    {
        // Circumstances data is optional, but validate boolean fields
        if (isset($data['is_pwd']) && !is_bool($data['is_pwd'])) {
            throw new \Exception('is_pwd must be a boolean value');
        }
    }

    private function validateServicesData(array $data): void
    {
        if (!is_array($data)) {
            throw new \Exception('Services data must be an array');
        }

        foreach ($data as $service) {
            if (!isset($service['type']) || !is_string($service['type'])) {
                throw new \Exception('Each service must have a type field');
            }
        }
    }

    private function validateResidenceData(array $data): void
    {
        if (isset($data['residence_type']) && !is_string($data['residence_type'])) {
            throw new \Exception('Residence type must be a string');
        }
    }
}
