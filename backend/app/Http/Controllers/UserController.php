<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Exception;

/**
 * User Controller for Admin User Management
 * 
 * Handles CRUD operations for admin users with role assignment,
 * authentication logging, permission enforcement, and audit trails.
 */
class UserController extends BaseApiController
{
    /**
     * Display a listing of admin users
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Check permissions
            $currentUser = $this->getAuthenticatedUser();
            if (!$currentUser || !$currentUser->hasPermission('users.view')) {
                return $this->forbiddenResponse('You do not have permission to view users');
            }

            $query = User::query()->with(['activityLogs' => function($query) {
                $query->latest()->limit(5);
            }]);

            // Apply filters
            if ($request->filled('role')) {
                $query->where('role', $request->role);
            }

            if ($request->filled('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            }

            // Apply sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            
            if (in_array($sortBy, ['name', 'email', 'role', 'is_active', 'created_at', 'last_login_at'])) {
                $query->orderBy($sortBy, $sortOrder);
            }

            // Get paginated results
            $perPage = min($request->get('per_page', 15), 100);
            $users = $query->paginate($perPage);

            // Transform data to include additional information
            $users->getCollection()->transform(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'role_label' => $user->role_label,
                    'is_active' => $user->is_active,
                    'last_login_at' => $user->last_login_at,
                    'last_seen' => $user->last_seen,
                    'is_online' => $user->is_online,
                    'initials' => $user->initials,
                    'permissions_count' => count($user->permissions ?? []),
                    'activity_summary' => $user->getActivitySummary(30),
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ];
            });

            $this->logActivity('viewed_users_list');

            return $this->paginatedResponse($users, 'Users retrieved successfully');

        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Store a newly created admin user
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // Check permissions
            $currentUser = $this->getAuthenticatedUser();
            if (!$currentUser || !$currentUser->hasPermission('users.create')) {
                return $this->forbiddenResponse('You do not have permission to create users');
            }

            // Validate request
            $validatedData = $this->validateRequest($request, User::validationRules());

            // Check if current user can create user with this role
            if (!$currentUser->canManageUser(new User(['role' => $validatedData['role']]))) {
                return $this->forbiddenResponse('You cannot create users with this role');
            }

            DB::beginTransaction();

            try {
                // Create user
                $user = User::create([
                    'name' => $validatedData['name'],
                    'email' => $validatedData['email'],
                    'password' => Hash::make($validatedData['password']),
                    'role' => $validatedData['role'],
                    'permissions' => $validatedData['permissions'] ?? User::ROLE_PERMISSIONS[$validatedData['role']] ?? [],
                    'is_active' => $validatedData['is_active'] ?? true,
                ]);

                // Log activity
                $this->logActivity('created', User::class, $user->id, $user->toArray());

                DB::commit();

                // Load relationships for response
                $user->load(['activityLogs' => function($query) {
                    $query->latest()->limit(5);
                }]);

                return $this->createdResponse([
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'role_label' => $user->role_label,
                    'is_active' => $user->is_active,
                    'permissions' => $user->permissions,
                    'created_at' => $user->created_at,
                ], 'User created successfully');

            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Display the specified admin user
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            // Check permissions
            $currentUser = $this->getAuthenticatedUser();
            if (!$currentUser || !$currentUser->hasPermission('users.view')) {
                return $this->forbiddenResponse('You do not have permission to view users');
            }

            $user = User::with([
                'activityLogs' => function($query) {
                    $query->latest()->limit(20);
                },
                'createdBookings' => function($query) {
                    $query->latest()->limit(10);
                },
                'createdQuotes' => function($query) {
                    $query->latest()->limit(10);
                },
                'approvedQuotes' => function($query) {
                    $query->latest()->limit(10);
                },
                'verifiedDocuments' => function($query) {
                    $query->latest()->limit(10);
                }
            ])->find($id);

            if (!$user) {
                return $this->notFoundResponse('User');
            }

            // Check if current user can view this user
            if (!$currentUser->canManageUser($user) && $currentUser->id !== $user->id) {
                return $this->forbiddenResponse('You cannot view this user');
            }

            $this->logActivity('viewed', User::class, $user->id);

            return $this->successResponse([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at,
                'role' => $user->role,
                'role_label' => $user->role_label,
                'is_active' => $user->is_active,
                'permissions' => $user->permissions,
                'last_login_at' => $user->last_login_at,
                'last_seen' => $user->last_seen,
                'is_online' => $user->is_online,
                'initials' => $user->initials,
                'activity_summary' => $user->getActivitySummary(30),
                'available_permissions' => $user->getAvailablePermissions(),
                'recent_activity' => $user->activityLogs->map(function($log) {
                    return [
                        'id' => $log->id,
                        'action' => $log->action,
                        'action_description' => $log->action_description,
                        'model_type' => $log->model_type,
                        'model_id' => $log->model_id,
                        'changes_summary' => $log->changes_summary,
                        'ip_address' => $log->ip_address,
                        'created_at' => $log->created_at,
                    ];
                }),
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ], 'User retrieved successfully');

        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Update the specified admin user
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            // Check permissions
            $currentUser = $this->getAuthenticatedUser();
            if (!$currentUser || !$currentUser->hasPermission('users.edit')) {
                return $this->forbiddenResponse('You do not have permission to edit users');
            }

            $user = User::find($id);
            if (!$user) {
                return $this->notFoundResponse('User');
            }

            // Check if current user can manage this user
            if (!$currentUser->canManageUser($user) && $currentUser->id !== $user->id) {
                return $this->forbiddenResponse('You cannot edit this user');
            }

            // Validate request
            $validatedData = $this->validateRequest($request, User::updateValidationRules($id));

            // If role is being changed, check permissions
            if (isset($validatedData['role']) && $validatedData['role'] !== $user->role) {
                if (!$currentUser->canManageUser(new User(['role' => $validatedData['role']]))) {
                    return $this->forbiddenResponse('You cannot assign this role');
                }
            }

            DB::beginTransaction();

            try {
                $originalData = $user->toArray();

                // Update user data
                if (isset($validatedData['name'])) {
                    $user->name = $validatedData['name'];
                }

                if (isset($validatedData['email'])) {
                    $user->email = $validatedData['email'];
                }

                if (isset($validatedData['password']) && !empty($validatedData['password'])) {
                    $user->password = Hash::make($validatedData['password']);
                }

                if (isset($validatedData['role'])) {
                    $user->role = $validatedData['role'];
                    // Update permissions based on new role if not explicitly provided
                    if (!isset($validatedData['permissions'])) {
                        $user->permissions = User::ROLE_PERMISSIONS[$validatedData['role']] ?? [];
                    }
                }

                if (isset($validatedData['permissions'])) {
                    $user->permissions = $validatedData['permissions'];
                }

                if (isset($validatedData['is_active'])) {
                    $user->is_active = $validatedData['is_active'];
                }

                $user->save();

                // Log activity with changes
                $changes = array_diff_assoc($user->toArray(), $originalData);
                $this->logActivity('updated', User::class, $user->id, $changes);

                DB::commit();

                return $this->updatedResponse([
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'role_label' => $user->role_label,
                    'is_active' => $user->is_active,
                    'permissions' => $user->permissions,
                    'updated_at' => $user->updated_at,
                ], 'User updated successfully');

            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Remove the specified admin user (soft delete)
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            // Check permissions
            $currentUser = $this->getAuthenticatedUser();
            if (!$currentUser || !$currentUser->hasPermission('users.delete')) {
                return $this->forbiddenResponse('You do not have permission to delete users');
            }

            $user = User::find($id);
            if (!$user) {
                return $this->notFoundResponse('User');
            }

            // Check if current user can manage this user
            if (!$currentUser->canManageUser($user)) {
                return $this->forbiddenResponse('You cannot delete this user');
            }

            // Prevent self-deletion
            if ($currentUser->id === $user->id) {
                return $this->conflictResponse('You cannot delete your own account');
            }

            DB::beginTransaction();

            try {
                // Soft delete the user
                $user->delete();

                // Log activity
                $this->logActivity('deleted', User::class, $user->id, ['deleted_at' => $user->deleted_at]);

                DB::commit();

                return $this->deletedResponse('User deleted successfully');

            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Activate a user account
     *
     * @param int $id
     * @return JsonResponse
     */
    public function activate(int $id): JsonResponse
    {
        try {
            // Check permissions
            $currentUser = $this->getAuthenticatedUser();
            if (!$currentUser || !$currentUser->hasPermission('users.edit')) {
                return $this->forbiddenResponse('You do not have permission to activate users');
            }

            $user = User::find($id);
            if (!$user) {
                return $this->notFoundResponse('User');
            }

            // Check if current user can manage this user
            if (!$currentUser->canManageUser($user)) {
                return $this->forbiddenResponse('You cannot activate this user');
            }

            if ($user->is_active) {
                return $this->conflictResponse('User is already active');
            }

            $user->activate();

            // Log activity
            $this->logActivity('activated', User::class, $user->id, ['is_active' => true]);

            return $this->successResponse([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_active' => $user->is_active,
            ], 'User activated successfully');

        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Deactivate a user account
     *
     * @param int $id
     * @return JsonResponse
     */
    public function deactivate(int $id): JsonResponse
    {
        try {
            // Check permissions
            $currentUser = $this->getAuthenticatedUser();
            if (!$currentUser || !$currentUser->hasPermission('users.edit')) {
                return $this->forbiddenResponse('You do not have permission to deactivate users');
            }

            $user = User::find($id);
            if (!$user) {
                return $this->notFoundResponse('User');
            }

            // Check if current user can manage this user
            if (!$currentUser->canManageUser($user)) {
                return $this->forbiddenResponse('You cannot deactivate this user');
            }

            // Prevent self-deactivation
            if ($currentUser->id === $user->id) {
                return $this->conflictResponse('You cannot deactivate your own account');
            }

            if (!$user->is_active) {
                return $this->conflictResponse('User is already inactive');
            }

            $user->deactivate();

            // Log activity
            $this->logActivity('deactivated', User::class, $user->id, ['is_active' => false]);

            return $this->successResponse([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_active' => $user->is_active,
            ], 'User deactivated successfully');

        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Update user permissions
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function updatePermissions(Request $request, int $id): JsonResponse
    {
        try {
            // Check permissions
            $currentUser = $this->getAuthenticatedUser();
            if (!$currentUser || !$currentUser->hasPermission('users.edit')) {
                return $this->forbiddenResponse('You do not have permission to update user permissions');
            }

            $user = User::find($id);
            if (!$user) {
                return $this->notFoundResponse('User');
            }

            // Check if current user can manage this user
            if (!$currentUser->canManageUser($user)) {
                return $this->forbiddenResponse('You cannot update permissions for this user');
            }

            // Validate permissions
            $validatedData = $this->validateRequest($request, [
                'permissions' => 'required|array',
                'permissions.*' => 'string|in:' . implode(',', array_keys(User::PERMISSIONS)),
            ]);

            $originalPermissions = $user->permissions;
            $user->syncPermissions($validatedData['permissions']);
            $user->save();

            // Log activity
            $this->logActivity('updated_permissions', User::class, $user->id, [
                'old_permissions' => $originalPermissions,
                'new_permissions' => $user->permissions,
            ]);

            return $this->successResponse([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'permissions' => $user->permissions,
                'updated_at' => $user->updated_at,
            ], 'User permissions updated successfully');

        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Get user activity logs
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function activityLogs(Request $request, int $id): JsonResponse
    {
        try {
            // Check permissions
            $currentUser = $this->getAuthenticatedUser();
            if (!$currentUser || !$currentUser->hasPermission('users.view')) {
                return $this->forbiddenResponse('You do not have permission to view user activity');
            }

            $user = User::find($id);
            if (!$user) {
                return $this->notFoundResponse('User');
            }

            // Check if current user can view this user's activity
            if (!$currentUser->canManageUser($user) && $currentUser->id !== $user->id) {
                return $this->forbiddenResponse('You cannot view this user\'s activity');
            }

            $query = ActivityLog::where('user_id', $id)->orderBy('created_at', 'desc');

            // Apply date filter if provided
            if ($request->filled('start_date') && $request->filled('end_date')) {
                $query->byDateRange($request->start_date, $request->end_date);
            }

            // Apply action filter if provided
            if ($request->filled('action')) {
                $query->byAction($request->action);
            }

            $perPage = min($request->get('per_page', 20), 100);
            $logs = $query->paginate($perPage);

            // Transform data
            $logs->getCollection()->transform(function ($log) {
                return [
                    'id' => $log->id,
                    'action' => $log->action,
                    'action_description' => $log->action_description,
                    'model_type' => $log->model_type,
                    'model_id' => $log->model_id,
                    'changes' => $log->changes,
                    'changes_summary' => $log->changes_summary,
                    'ip_address' => $log->ip_address,
                    'user_agent' => $log->user_agent,
                    'created_at' => $log->created_at,
                ];
            });

            return $this->paginatedResponse($logs, 'User activity logs retrieved successfully');

        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Get available roles and permissions
     *
     * @return JsonResponse
     */
    public function rolesAndPermissions(): JsonResponse
    {
        try {
            // Check permissions
            $currentUser = $this->getAuthenticatedUser();
            if (!$currentUser || !$currentUser->hasPermission('users.view')) {
                return $this->forbiddenResponse('You do not have permission to view roles and permissions');
            }

            return $this->successResponse([
                'roles' => [
                    User::ROLE_SUPER_ADMIN => 'Super Administrator',
                    User::ROLE_ADMIN => 'Administrator',
                    User::ROLE_MANAGER => 'Manager',
                    User::ROLE_OPERATOR => 'Operator',
                ],
                'permissions' => User::PERMISSIONS,
                'role_permissions' => User::ROLE_PERMISSIONS,
                'available_permissions' => $currentUser->getAvailablePermissions(),
            ], 'Roles and permissions retrieved successfully');

        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }
}