<?php

require_once __DIR__ . '/../Repositories/UserRepository.php';
require_once __DIR__ . '/../Repositories/RoleRepository.php';
require_once __DIR__ . '/../Security/Hash.php';

class UserService
{
    private UserRepository $userRepository;
    private RoleRepository $roleRepository;

    private const ALLOWED_ROLES = [
        'Admin',
        'Provider',
        'Nurse',
        'Patient',
        'Pharmacist'
    ];

    public function __construct(
        UserRepository $userRepository,
        RoleRepository $roleRepository
    ) {
        $this->userRepository = $userRepository;
        $this->roleRepository = $roleRepository;
    }


    /*
    |--------------------------------------------------------------------------
    | CREATE USER
    |--------------------------------------------------------------------------
    */
    public function createUser(
        array $data,
        int $tenantId
    ): array {

        if (empty($data['name'])) {
            throw new Exception(
                'Name is required'
            );
        }

        if (
            empty($data['email']) ||
            !filter_var(
                $data['email'],
                FILTER_VALIDATE_EMAIL
            )
        ) {
            throw new Exception(
                'A valid email is required'
            );
        }

        if (
            empty($data['password']) ||
            strlen($data['password']) < 8
        ) {
            throw new Exception(
                'Password must be at least 8 characters'
            );
        }

        // Default role
        $role = $data['role'] ?? 'Patient';

        if (
            !in_array(
                $role,
                self::ALLOWED_ROLES,
                true
            )
        ) {
            throw new Exception(
                'Invalid role. Allowed roles: ' .
                implode(', ', self::ALLOWED_ROLES)
            );
        }

        // Check duplicate email
        if (
            $this->userRepository->findByEmail(
                $data['email']
            )
        ) {
            throw new Exception(
                'Email already registered'
            );
        }

        // Find role
        $roleRecord =
            $this->roleRepository->findByName(
                $role
            );

        if (!$roleRecord) {
            throw new Exception(
                'Role not found'
            );
        }

        // Create user
        $userId =
            $this->userRepository->create([
                'name'     => $data['name'],
                'email'    => $data['email'],
                'password' => Hash::make(
                    $data['password']
                )
            ]);

        // Assign role
        $this->roleRepository->assignToUser(
            $userId,
            (int) $roleRecord['id']
        );

        return $this->getUser(
            $userId,
            $tenantId
        );
    }


    /*
    |--------------------------------------------------------------------------
    | GET SINGLE USER
    |--------------------------------------------------------------------------
    */
    public function getUser(
        int $id,
        int $tenantId
    ): array {

        $user =
            $this->userRepository->findById($id);

        if (!$user) {
            throw new Exception(
                'User not found'
            );
        }

        // Never return password
        unset($user['password']);

        // Add roles
        $user['roles'] = array_column(
            $this->roleRepository->getRolesForUser($id),
            'name'
        );

        return $user;
    }


    /*
    |--------------------------------------------------------------------------
    | GET ALL USERS
    |--------------------------------------------------------------------------
    */
    public function getUsers(
        int $tenantId,
        ?string $role = null
    ): array {

        $users = $role
            ? $this->userRepository->findAllByRole($role)
            : $this->userRepository->findAll();

        foreach ($users as &$user) {

            // Never return password
            unset($user['password']);

            // Add roles
            $user['roles'] = array_column(
                $this->roleRepository->getRolesForUser(
                    (int) $user['id']
                ),
                'name'
            );
        }

        unset($user);

        return $users;
    }


    /*
    |--------------------------------------------------------------------------
    | UPDATE USER / PROFILE
    |--------------------------------------------------------------------------
    */
    public function updateProfile(
        int $id,
        int $tenantId,
        array $data
    ): array {

        $user =
            $this->userRepository->findById($id);

        if (!$user) {
            throw new Exception(
                'User not found'
            );
        }

        $updates = [];


        /*
        |--------------------------------------------------------------------------
        | Update Name
        |--------------------------------------------------------------------------
        */
        if (!empty($data['name'])) {
            $updates['name'] =
                $data['name'];
        }


        /*
        |--------------------------------------------------------------------------
        | Update Email
        |--------------------------------------------------------------------------
        */
        if (
            !empty($data['email']) &&
            $data['email'] !== $user['email']
        ) {

            if (
                !filter_var(
                    $data['email'],
                    FILTER_VALIDATE_EMAIL
                )
            ) {
                throw new Exception(
                    'Invalid email format'
                );
            }

            if (
                $this->userRepository->findByEmail(
                    $data['email']
                )
            ) {
                throw new Exception(
                    'Email already in use'
                );
            }

            $updates['email'] =
                $data['email'];
        }


        /*
        |--------------------------------------------------------------------------
        | Update Basic Details
        |--------------------------------------------------------------------------
        */
        if (!empty($updates)) {

            $this->userRepository->update(
                $id,
                $updates
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Update Password
        |--------------------------------------------------------------------------
        */
        if (!empty($data['password'])) {

            if (
                strlen($data['password']) < 8
            ) {
                throw new Exception(
                    'Password must be at least 8 characters'
                );
            }

            $this->userRepository->updatePassword(
                $id,
                Hash::make(
                    $data['password']
                )
            );
        }


        return $this->getUser(
            $id,
            $tenantId
        );
    }


    /*
    |--------------------------------------------------------------------------
    | ASSIGN ROLE
    |--------------------------------------------------------------------------
    */
    public function assignRole(
        int $userId,
        int $tenantId,
        string $role
    ): array {

        $user =
            $this->userRepository->findById(
                $userId
            );

        if (!$user) {
            throw new Exception(
                'User not found'
            );
        }

        if (
            !in_array(
                $role,
                self::ALLOWED_ROLES,
                true
            )
        ) {
            throw new Exception(
                'Invalid role. Allowed roles: ' .
                implode(', ', self::ALLOWED_ROLES)
            );
        }

        $roleRecord =
            $this->roleRepository->findByName(
                $role
            );

        if (!$roleRecord) {
            throw new Exception(
                'Role not found'
            );
        }

        $this->roleRepository->assignToUser(
            $userId,
            (int) $roleRecord['id']
        );

        return $this->getUser(
            $userId,
            $tenantId
        );
    }


    /*
    |--------------------------------------------------------------------------
    | REMOVE ROLE
    |--------------------------------------------------------------------------
    */
    public function removeRole(
        int $userId,
        int $tenantId,
        string $role
    ): array {

        $user =
            $this->userRepository->findById(
                $userId
            );

        if (!$user) {
            throw new Exception(
                'User not found'
            );
        }

        if (
            !in_array(
                $role,
                self::ALLOWED_ROLES,
                true
            )
        ) {
            throw new Exception(
                'Invalid role. Allowed roles: ' .
                implode(', ', self::ALLOWED_ROLES)
            );
        }

        $roleRecord =
            $this->roleRepository->findByName(
                $role
            );

        if (!$roleRecord) {
            throw new Exception(
                'Role not found'
            );
        }

        $this->roleRepository->removeFromUser(
            $userId,
            (int) $roleRecord['id']
        );

        return $this->getUser(
            $userId,
            $tenantId
        );
    }


    /*
    |--------------------------------------------------------------------------
    | DELETE USER
    |--------------------------------------------------------------------------
    */
    public function deleteUser(
        int $id,
        int $tenantId
    ): bool {

        $user =
            $this->userRepository->findById($id);

        if (!$user) {
            throw new Exception(
                'User not found'
            );
        }

        return $this->userRepository->delete($id);
    }
}