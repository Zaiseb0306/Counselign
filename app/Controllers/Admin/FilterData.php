<?php

namespace App\Controllers\Admin;


use App\Helpers\SecureLogHelper;
use App\Controllers\BaseController;
use App\Models\CounselorModel;
use App\Models\StudentPersonalInfoModel;
use App\Models\StudentAcademicInfoModel;
use CodeIgniter\API\ResponseTrait;

class FilterData extends BaseController
{
    use ResponseTrait;

    /**
     * Get all counselor names for filter dropdown
     */
    public function getCounselors()
    {
        try {
            $counselorModel = new CounselorModel();
            $counselors = $counselorModel->select('counselor_id, name')
                ->orderBy('name', 'ASC')
                ->findAll();

            return $this->respond([
                'success' => true,
                'data' => $counselors
            ]);
        } catch (\Exception $e) {
            return $this->respond([
                'success' => false,
                'message' => 'Error fetching counselors: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all student names for filter dropdown
     */
    public function getStudents()
    {
        try {
            $studentModel = new StudentPersonalInfoModel();
            $students = $studentModel->select('student_id, first_name, last_name')
                ->orderBy('last_name', 'ASC')
                ->orderBy('first_name', 'ASC')
                ->findAll();

            // Format student names for display
            $formattedStudents = array_map(function($student) {
                return [
                    'student_id' => $student['student_id'],
                    'full_name' => trim($student['first_name'] . ' ' . $student['last_name']),
                    'first_name' => $student['first_name'],
                    'last_name' => $student['last_name']
                ];
            }, $students);

            return $this->respond([
                'success' => true,
                'data' => $formattedStudents
            ]);
        } catch (\Exception $e) {
            return $this->respond([
                'success' => false,
                'message' => 'Error fetching students: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all courses for filter dropdown
     */
    public function getCourses()
    {
        try {
            $academicModel = new StudentAcademicInfoModel();
            $courses = $academicModel->select('course')
                ->distinct()
                ->where('course IS NOT NULL')
                ->where('course !=', '')
                ->orderBy('course', 'ASC')
                ->findAll();

            // Format courses for dropdown
            $formattedCourses = array_map(function($course) {
                return [
                    'value' => $course['course'],
                    'label' => $course['course']
                ];
            }, $courses);

            return $this->respond([
                'success' => true,
                'data' => $formattedCourses
            ]);
        } catch (\Exception $e) {
            return $this->respond([
                'success' => false,
                'message' => 'Error fetching courses: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all year levels for filter dropdown
     */
    public function getYearLevels()
    {
        try {
            $academicModel = new StudentAcademicInfoModel();
            $yearLevels = $academicModel->select('year_level')
                ->distinct()
                ->where('year_level IS NOT NULL')
                ->where('year_level !=', '')
                ->orderBy('year_level', 'ASC')
                ->findAll();

            // Format year levels for dropdown
            $formattedYearLevels = array_map(function($yearLevel) {
                return [
                    'value' => $yearLevel['year_level'],
                    'label' => $yearLevel['year_level']
                ];
            }, $yearLevels);

            return $this->respond([
                'success' => true,
                'data' => $formattedYearLevels
            ]);
        } catch (\Exception $e) {
            return $this->respond([
                'success' => false,
                'message' => 'Error fetching year levels: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a map of student_id -> { course, year_level }
     */
    public function getStudentAcademicMap()
    {
        try {
            $academicModel = new StudentAcademicInfoModel();
            $rows = $academicModel->select('student_id, course, year_level')->findAll();

            $map = [];
            foreach ($rows as $row) {
                // Last write wins if duplicates; acceptable for export filtering context
                $map[$row['student_id']] = [
                    'course' => $row['course'] ?? '',
                    'year_level' => $row['year_level'] ?? ''
                ];
            }

            return $this->respond([
                'success' => true,
                'data' => $map
            ]);
        } catch (\Exception $e) {
            return $this->respond([
                'success' => false,
                'message' => 'Error fetching academic map: ' . $e->getMessage()
            ], 500);
        }
    }
}
