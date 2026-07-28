<?php

namespace App\Repositories\Contracts;

interface StudentRepositoryInterface
{
    public function getAllStudents(array $filters = [], int $perPage = 15);
    public function getStudentById(int $userId);
    public function createStudent(array $data);
    public function updateStudent(int $userId, array $data);
    public function deleteStudent(int $userId);
    
    // Complex Dashboard Aggregations
    public function getStudentDashboardMetrics(int $userId);
    public function getStudentPurchasedCourses(int $userId);
    public function getStudentInternships(int $userId);
    public function getStudentJobs(int $userId);
    public function getStudentAttendance(int $userId);
}
