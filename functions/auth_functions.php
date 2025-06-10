<?php
require_once __DIR__ . '/../config/database.php';

function registerUser($username, $password, $name, $email, $phone, $address, $role = 'customer') {
    global $pdo;
    
    if (empty($name)) {
        throw new Exception("Nama lengkap harus diisi");
    }
    
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("INSERT INTO users (username, password, name, email, phone, address, role) VALUES (?, ?, ?, ?, ?, ?, ?)");
    return $stmt->execute([$username, $hashedPassword, $name, $email, $phone, $address, $role]);
}

function loginUser($username, $password) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify($password, $user['password'])) {
        // Simpan semua data penting di session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['name'] = $user['name'] ?? $user['username']; // Gunakan username jika name tidak ada
        $_SESSION['role'] = $user['role'] ?? 'customer'; // Default role jika tidak ada
        
        // Redirect berdasarkan role
        if (in_array($_SESSION['role'], ['admin', 'owner'])) {
            header('Location: admin/dashboard.php');
        } else {
            header('Location: dashboard.php');
        }
        exit();
    }
    return false;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function isOwner() {
    return isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === 'owner';
}

function isCustomer() {
    return isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === 'customer';
}

function logout() {
    session_unset();
    session_destroy();
}
?>