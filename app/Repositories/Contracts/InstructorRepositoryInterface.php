<?php

namespace App\Repositories\Contracts;

interface InstructorRepositoryInterface
{
    public function getAllInstructors(array $filters = [], int $perPage = 15);
    public function getInstructorById(int $instructorId);
    
    public function createInstructor(array $data);

    public function updateInstructor(int $instructorId, array $data);
    
    public function getInstructorCourses(int $instructorId, array $filters = [], int $perPage = 15);
    
    public function getDashboardMetrics();
    public function getInstructorMetrics(int $instructorId);
}
