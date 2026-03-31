<?php

$ROLES_PERMISSIONS = [
    'super-admin' => [
        'label' => 'Super Admin',
        'permissions' => ['orders', 'tasks', 'resources', 'roles', 'reports', 'settings', 'users', 'leaves'],
        'can_manage_users' => true,
        'can_manage_roles' => true,
    ],
    'staff' => [
        'label' => 'Staff',
        'permissions' => ['tasks'],
        'can_manage_users' => false,
        'can_manage_roles' => false,
    ],
];

function requireAuth() {
    if (!isset($_SESSION['user']) || !isset($_SESSION['user_id']) || !isset($_SESSION['role_slug'])) {
        header("Location: index.php");
        exit();
    }
}

function hasPermission($module) {
    global $ROLES_PERMISSIONS;
    
    if (!isset($_SESSION['role_slug'])) {
        return false;
    }
    
    $role = $_SESSION['role_slug'];
    
    if (!isset($ROLES_PERMISSIONS[$role])) {
        return false;
    }
    
    return in_array($module, $ROLES_PERMISSIONS[$role]['permissions']);
}

function isRole($role) {
    return isset($_SESSION['role_slug']) && $_SESSION['role_slug'] === $role;
}

function isSuperAdmin() {
    return isRole('super-admin');
}

function requirePermission($module) {
    if (!hasPermission($module)) {
        $_SESSION['error'] = "Access Denied: You don't have permission to access this module.";
        header("Location: dashboard.php");
        exit();
    }
}

function requireSuperAdmin() {
    if (!isSuperAdmin()) {
        $_SESSION['error'] = "Access Denied: Super Admin access required.";
        header("Location: dashboard.php");
        exit();
    }
}

function getRoleLabel($role_slug) {
    global $ROLES_PERMISSIONS;
    
    if (isset($ROLES_PERMISSIONS[$role_slug])) {
        return $ROLES_PERMISSIONS[$role_slug]['label'];
    }
    
    return ucfirst(str_replace('-', ' ', $role_slug));
}

function getCurrentUserPermissions() {
    global $ROLES_PERMISSIONS;
    
    if (!isset($_SESSION['role_slug'])) {
        return [];
    }
    
    $role = $_SESSION['role_slug'];
    
    if (isset($ROLES_PERMISSIONS[$role])) {
        return $ROLES_PERMISSIONS[$role]['permissions'];
    }
    
    return [];
}

function canManageUsers() {
    global $ROLES_PERMISSIONS;
    
    if (!isset($_SESSION['role_slug'])) {
        return false;
    }
    
    $role = $_SESSION['role_slug'];
    
    if (isset($ROLES_PERMISSIONS[$role])) {
        return $ROLES_PERMISSIONS[$role]['can_manage_users'] ?? false;
    }
    
    return false;
}

function canManageRoles() {
    global $ROLES_PERMISSIONS;
    
    if (!isset($_SESSION['role_slug'])) {
        return false;
    }
    
    $role = $_SESSION['role_slug'];
    
    if (isset($ROLES_PERMISSIONS[$role])) {
        return $ROLES_PERMISSIONS[$role]['can_manage_roles'] ?? false;
    }
    
    return false;
}

?>
