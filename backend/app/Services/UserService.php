<?php

require_once __DIR__ . '/../Repositories/UserRepository.php';
require_once __DIR__ . '/../Repositories/RoleRepository.php';
require_once __DIR__ . '/../Security/Hash.php';

class UserService
{
    private UserRepository $userRepository;
    private RoleRepository $roleRepository;

    private const ALLOWED_ROLES = ['Admin', 'Provider', 'Nurse', 'Patient', 'Pharmacist'];

    public function __construct(UserRepository $userRepository, RoleRepository $roleRepository)
    {
        $this->userRepository = $userRepository;
        $this->roleRepository = $roleRepository;
    }

    public function createUser(array $data, int $tenantId): array
    {
        if (empty($data['name'])) {
            throw new Exception('Name is required');
        }

        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception('A valid email is required');
        }

        if (empty($data['password']) || strlen($data['password']) < 6) {
            throw new Exception('Password must be at least 6 characters');
        }

        $role = $data['role'] ?? 'Patient';

        if (!in_array($role, self::ALLOWED_ROLES, true)) {
            throw new Exception('Invalid role. Allowed roles: ' . implode(', ', self::ALLOWED_ROLES));
        }

        if ($this->userRepository->findByEmail($data['email'])) {
            throw new Exception('Email already registered');
        }

        $roleRecord = $this->roleRepository->findByName($role);

        if (!$roleRecord) {
            throw new Exception('Role not found. Run the roles seed in database.sql');
        }

        $userId = $this->userRepository->create([
            'tenant_id' => $tenantId,
            'name'      => $data['name'],
            'email'     => $data['email'],
            'password'  => Hash::make($data['password'])
        ]);

        $this->roleRepository->assignToUser($userId, (int) $roleRecord['id']);

        return $this->getUser($userId, $tenantId);
    }

    public function getUser(int $id, int $tenantId): array
    {
        $user = $this->userRepository->findById($id);

        if (!$user || (int) $user['tenant_id'] !== $tenantId) {
            throw new Exception('User not found');
        }

        unset($user['password']);
        $user['roles'] = array_column($this->roleRepository->getRolesForUser($id), 'name');

        return $user;
    }

    public function getUsers(int $tenantId, ?string $role = null): array
    {
        $users = $role
            ? $this->userRepository->findAllByTenantAndRole($tenantId, $role)
            : $this->userRepository->findAllByTenant($tenantId);

        foreach ($users as &$user) {
            unset($user['password']);
            $user['roles'] = array_column(
                $this->roleRepository->getRolesForUser((int) $user['id']),
                'name'
            );
        }
        unset($user);

        return $users;
    }

    public function updateProfile(int $id, int $tenantId, array $data): array
    {
        $user = $this->userRepository->findById($id);

        if (!$user || (int) $user['tenant_id'] !== $tenantId) {
            throw new Exception('User not found');
        }

        $updates = [];

        if (!empty($data['name'])) {
            $updates['name'] = $data['name'];
        }

        if (!empty($data['email']) && $data['email'] !== $user['email']) {
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Invalid email format');
            }

            if ($this->userRepository->findByEmail($data['email'])) {
                throw new Exception('Email already in use');
            }

            $updates['email'] = $data['email'];
        }

        if (!empty($updates)) {
            $this->userRepository->update($id, $updates);
        }

        if (!empty($data['password'])) {
            if (strlen($data['password']) < 6) {
                throw new Exception('Password must be at least 6 characters');
            }

            $this->userRepository->updatePassword($id, Hash::make($data['password']));
        }

        return $this->getUser($id, $tenantId);
    }

    public function assignRole(int $userId, int $tenantId, string $role): array
    {
        $user = $this->userRepository->findById($userId);

        if (!$user || (int) $user['tenant_id'] !== $tenantId) {
            throw new Exception('User not found');
        }

        if (!in_array($role, self::ALLOWED_ROLES, true)) {
            throw new Exception('Invalid role. Allowed roles: ' . implode(', ', self::ALLOWED_ROLES));
        }

        $roleRecord = $this->roleRepository->findByName($role);

        if (!$roleRecord) {
            throw new Exception('Role not found');
        }

        $this->roleRepository->assignToUser($userId, (int) $roleRecord['id']);

        return $this->getUser($userId, $tenantId);
    }

    public function removeRole(int $userId, int $tenantId, string $role): array
    {
        $user = $this->userRepository->findById($userId);

        if (!$user || (int) $user['tenant_id'] !== $tenantId) {
            throw new Exception('User not found');
        }

        $roleRecord = $this->roleRepository->findByName($role);

        if (!$roleRecord) {
            throw new Exception('Role not found');
        }

        $this->roleRepository->removeFromUser($userId, (int) $roleRecord['id']);

        return $this->getUser($userId, $tenantId);
    }

    public function deleteUser(int $id, int $tenantId): bool
    {
        $user = $this->userRepository->findById($id);

        if (!$user || (int) $user['tenant_id'] !== $tenantId) {
            throw new Exception('User not found');
        }

        return $this->userRepository->delete($id);
    }
}