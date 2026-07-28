<?php

namespace App\Repositories\Contracts;

interface RoleRepositoryInterface
{
    public function getAll();
    public function findById(int $id);
    public function create(array $data);
    public function update(int $id, array $data);
    public function delete(int $id);
    public function getAuditLogs(int $roleId = null);
}
