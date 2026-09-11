<?php

require_once __DIR__ . '/../Services/UserService.php';
require_once __DIR__ . '/../Helpers/Response.php';

class UserController
{
    private UserService $userService;

    public function __construct(
        UserService $userService
    ) {
        $this->userService = $userService;
    }


    /*
    |--------------------------------------------------------------------------
    | CREATE USER
    |--------------------------------------------------------------------------
    | POST /users
    | Admin
    |--------------------------------------------------------------------------
    */
    public function create(
        array $data,
        int $tenantId
    ): void {

        try {

            $user =
                $this->userService->createUser(
                    $data,
                    $tenantId
                );

            Response::success(
                $user,
                'User created successfully',
                201
            );

        } catch (Exception $e) {

            Response::error(
                $e->getMessage(),
                422
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | GET ALL USERS
    |--------------------------------------------------------------------------
    | GET /users?role=Nurse
    | Admin
    |--------------------------------------------------------------------------
    */
    public function index(
        int $tenantId
    ): void {

        try {

            $role =
                $_GET['role'] ?? null;

            $users =
                $this->userService->getUsers(
                    $tenantId,
                    $role
                );

            Response::success(
                $users,
                'Users fetched successfully'
            );

        } catch (Exception $e) {

            Response::error(
                $e->getMessage(),
                422
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | GET SINGLE USER
    |--------------------------------------------------------------------------
    | GET /users/{id}
    | Admin
    |--------------------------------------------------------------------------
    */
    public function show(
        int $id,
        int $tenantId
    ): void {

        try {

            $user =
                $this->userService->getUser(
                    $id,
                    $tenantId
                );

            Response::success(
                $user,
                'User fetched successfully'
            );

        } catch (Exception $e) {

            Response::error(
                $e->getMessage(),
                404
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | GET OWN PROFILE
    |--------------------------------------------------------------------------
    | GET /profile
    | Self
    |--------------------------------------------------------------------------
    */
    public function profile(
        int $userId,
        int $tenantId
    ): void {

        try {

            $user =
                $this->userService->getUser(
                    $userId,
                    $tenantId
                );

            Response::success(
                $user,
                'Profile fetched successfully'
            );

        } catch (Exception $e) {

            Response::error(
                $e->getMessage(),
                404
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | UPDATE OWN PROFILE
    |--------------------------------------------------------------------------
    | PUT /profile
    | Self
    |--------------------------------------------------------------------------
    */
    public function updateProfile(
        array $data,
        int $userId,
        int $tenantId
    ): void {

        try {

            $user =
                $this->userService->updateProfile(
                    $userId,
                    $tenantId,
                    $data
                );

            Response::success(
                $user,
                'Profile updated successfully'
            );

        } catch (Exception $e) {

            Response::error(
                $e->getMessage(),
                422
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | UPDATE USER
    |--------------------------------------------------------------------------
    | PUT /users/{id}
    | Admin
    |--------------------------------------------------------------------------
    */
    public function update(
        int $id,
        array $data,
        int $tenantId
    ): void {

        try {

            $user =
                $this->userService->updateProfile(
                    $id,
                    $tenantId,
                    $data
                );

            Response::success(
                $user,
                'User updated successfully'
            );

        } catch (Exception $e) {

            Response::error(
                $e->getMessage(),
                422
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | ASSIGN ROLE
    |--------------------------------------------------------------------------
    | POST /users/{id}/roles
    | { "role": "Nurse" }
    | Admin
    |--------------------------------------------------------------------------
    */
    public function assignRole(
        int $id,
        array $data,
        int $tenantId
    ): void {

        if (
            !isset($data['role']) ||
            trim((string) $data['role']) === ''
        ) {
            Response::error(
                'Role is required',
                422
            );
        }

        try {

            $user =
                $this->userService->assignRole(
                    $id,
                    $tenantId,
                    $data['role']
                );

            Response::success(
                $user,
                'Role assigned successfully'
            );

        } catch (Exception $e) {

            Response::error(
                $e->getMessage(),
                422
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | REMOVE ROLE
    |--------------------------------------------------------------------------
    | DELETE /users/{id}/roles/{role}
    | Admin
    |--------------------------------------------------------------------------
    */
    public function removeRole(
        int $id,
        string $role,
        int $tenantId
    ): void {

        try {

            $user =
                $this->userService->removeRole(
                    $id,
                    $tenantId,
                    $role
                );

            Response::success(
                $user,
                'Role removed successfully'
            );

        } catch (Exception $e) {

            Response::error(
                $e->getMessage(),
                422
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | DELETE USER
    |--------------------------------------------------------------------------
    | DELETE /users/{id}
    | Admin
    |--------------------------------------------------------------------------
    */
    public function delete(
        int $id,
        int $tenantId
    ): void {

        try {

            $this->userService->deleteUser(
                $id,
                $tenantId
            );

            Response::success(
                null,
                'User deleted successfully'
            );

        } catch (Exception $e) {

            Response::error(
                $e->getMessage(),
                404
            );
        }
    }
}