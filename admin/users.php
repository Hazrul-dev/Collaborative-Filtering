<?php
$pageTitle = "Kelola Pengguna";
$isAdminPage = true;
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../functions/auth_functions.php';
require_once __DIR__ . '/../functions/user_functions.php';

// Periksa apakah user adalah admin
if (!isAdmin()) {
    header('Location: /login.php');
    exit();
}

$action = $_GET['action'] ?? 'list';
$userId = $_GET['id'] ?? null;

// Tangani aksi yang berbeda
switch ($action) {
    case 'add':
        handleAddUser();
        break;
    case 'edit':
        handleEditUser($userId);
        break;
    case 'delete':
        handleDeleteUser($userId);
        break;
    case 'list':
    default:
        showUserList();
        break;
}

function handleAddUser() {
    global $pdo;
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $username = $_POST['username'];
        $password = $_POST['password'];
        $name = $_POST['name'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        $address = $_POST['address'];
        $role = $_POST['role'];
        
        if (registerUser($username, $password, $name, $email, $phone, $address, $role)) {
            $_SESSION['success'] = "Pengguna berhasil ditambahkan!";
            header('Location: users.php');
            exit();
        } else {
            $error = "Gagal menambahkan pengguna. Username atau email mungkin sudah digunakan.";
        }
    }
    
    showUserForm('Tambah Pengguna');
}

function handleEditUser($userId) {
    global $pdo;
    
    $user = getUserById($userId);
    if (!$user) {
        $_SESSION['error'] = "Pengguna tidak ditemukan!";
        header('Location: users.php');
        exit();
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = $_POST['name'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        $address = $_POST['address'];
        $role = $_POST['role'];
        
        $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, phone = ?, address = ?, role = ? WHERE id = ?");
        if ($stmt->execute([$name, $email, $phone, $address, $role, $userId])) {
            $_SESSION['success'] = "Pengguna berhasil diperbarui!";
            header('Location: users.php');
            exit();
        } else {
            $error = "Gagal memperbarui pengguna.";
        }
    }
    
    showUserForm('Edit Pengguna', $user);
}

function handleDeleteUser($userId) {
    global $pdo;
    
    // Jangan izinkan menghapus diri sendiri
    if ($userId == $_SESSION['user_id']) {
        $_SESSION['error'] = "Anda tidak dapat menghapus akun sendiri!";
        header('Location: users.php');
        exit();
    }
    
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    if ($stmt->execute([$userId])) {
        $_SESSION['success'] = "Pengguna berhasil dihapus!";
    } else {
        $_SESSION['error'] = "Gagal menghapus pengguna.";
    }
    
    header('Location: users.php');
    exit();
}

function showUserList() {
    global $pdo;
    
    $search = $_GET['search'] ?? null;
    $role = $_GET['role'] ?? null;
    $page = max(1, $_GET['page'] ?? 1);
    $limit = 10;
    $offset = ($page - 1) * $limit;
    
    $query = "SELECT * FROM users";
    $countQuery = "SELECT COUNT(*) FROM users";
    $params = [];
    $where = [];
    
    if ($search) {
        $where[] = "(name LIKE ? OR username LIKE ? OR email LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    if ($role) {
        $where[] = "role = ?";
        $params[] = $role;
    }
    
    if (!empty($where)) {
        $query .= " WHERE " . implode(" AND ", $where);
        $countQuery .= " WHERE " . implode(" AND ", $where);
    }
    
    $query .= " ORDER BY name LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $pdo->prepare($countQuery);
    $stmt->execute($params);
    $totalUsers = $stmt->fetchColumn();
    $totalPages = ceil($totalUsers / $limit);
    ?>
    <div class="admin-container">
        <div class="admin-sidebar">
            <div class="admin-profile">
            <img src="../assets/images/IF.jpg" alt="Admin Avatar">
                <h3><?php echo htmlspecialchars($_SESSION['name']); ?></h3>
                <p>Admin</p>
            </div>
            
            <ul class="admin-menu">
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="products.php"><i class="fas fa-box"></i> Kelola Produk</a></li>
                <li><a href="orders.php"><i class="fas fa-shopping-bag"></i> Kelola Pesanan</a></li>
                <li class="active"><a href="users.php"><i class="fas fa-users"></i> Kelola Pengguna</a></li>
                <li><a href="recommendations.php"><i class="fas fa-star"></i> Rekomendasi Produk</a></li>
                <li><a href="preorders.php"><i class="fas fa-calendar-check"></i> Pre Order</a></li>
                <li><a href="reports.php"><i class="fas fa-chart-bar"></i> Laporan</a></li>
            </ul>
        </div>
        
        <div class="admin-content">
            <div class="admin-header">
                <h1>Kelola Pengguna</h1>
                <a href="users.php?action=add" class="btn"><i class="fas fa-plus"></i> Tambah Pengguna</a>
            </div>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
            <?php endif; ?>
            
            <div class="admin-filters">
                <form method="get" action="users.php">
                    <div class="form-group">
                        <input type="text" name="search" placeholder="Cari nama, username atau email..." value="<?php echo htmlspecialchars($search ?? ''); ?>">
                        <button type="submit" class="btn-small"><i class="fas fa-search"></i></button>
                    </div>
                    
                    <div class="form-group">
                        <select name="role" onchange="this.form.submit()">
                            <option value="">Semua Role</option>
                            <option value="admin" <?php echo $role === 'admin' ? 'selected' : ''; ?>>Admin</option>
                            <option value="owner" <?php echo $role === 'owner' ? 'selected' : ''; ?>>Owner</option>
                            <option value="customer" <?php echo $role === 'customer' ? 'selected' : ''; ?>>Customer</option>
                        </select>
                    </div>
                </form>
            </div>
            
            <div class="admin-table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Bergabung</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="6" class="text-center">Tidak ada pengguna ditemukan</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <span class="role-badge <?php echo $user['role']; ?>">
                                            <?php echo ucfirst($user['role']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d M Y', strtotime($user['created_at'])); ?></td>
                                    <td class="actions">
                                        <a href="users.php?action=edit&id=<?php echo $user['id']; ?>" class="btn-small btn-edit"><i class="fas fa-edit"></i></a>
                                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                            <a href="users.php?action=delete&id=<?php echo $user['id']; ?>" class="btn-small btn-delete" onclick="return confirm('Apakah Anda yakin ingin menghapus pengguna ini?')"><i class="fas fa-trash"></i></a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="users.php?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" class="page-link">&laquo; Sebelumnya</a>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="users.php?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" class="page-link <?php echo $i == $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                <?php endfor; ?>
                
                <?php if ($page < $totalPages): ?>
                    <a href="users.php?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" class="page-link">Selanjutnya &raquo;</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

function showUserForm($title, $user = null) {
    global $error;
    ?>
    <div class="admin-container">
        <div class="admin-sidebar">
            <div class="admin-profile">
            <img src="../assets/images/IF.jpg" alt="Admin Avatar">
                <h3><?php echo htmlspecialchars($_SESSION['name']); ?></h3>
                <p>Admin</p>
            </div>
            
            <ul class="admin-menu">
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="products.php"><i class="fas fa-box"></i> Kelola Produk</a></li>
                <li><a href="orders.php"><i class="fas fa-shopping-bag"></i> Kelola Pesanan</a></li>
                <li class="active"><a href="users.php"><i class="fas fa-users"></i> Kelola Pengguna</a></li>
                <li><a href="recommendations.php"><i class="fas fa-star"></i> Rekomendasi Produk</a></li>
                <li><a href="preorders.php"><i class="fas fa-calendar-check"></i> Pre Order</a></li>
                <li><a href="reports.php"><i class="fas fa-chart-bar"></i> Laporan</a></li>
            </ul>
        </div>
        
        <div class="admin-content">
            <div class="admin-header">
                <h1><?php echo $title; ?></h1>
                <a href="users.php" class="btn"><i class="fas fa-arrow-left"></i> Kembali</a>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="alert error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="post" class="admin-form">
                <?php if (!$user): ?>
                <div class="form-row">
                    <div class="form-group">
                        <label for="username">Username:</label>
                        <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password:</label>
                        <input type="password" id="password" name="password" <?php echo !$user ? 'required' : ''; ?>>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="name">Nama Lengkap:</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Nomor Telepon:</label>
                        <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="address">Alamat:</label>
                    <textarea id="address" name="address" rows="3" required><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="role">Role:</label>
                    <select id="role" name="role" required>
                        <option value="customer" <?php echo (isset($user['role']) && $user['role'] === 'customer' ? 'selected' : ''); ?>>Customer</option>
                        <option value="admin" <?php echo (isset($user['role']) && $user['role'] === 'admin' ? 'selected' : ''); ?>>Admin</option>
                        <option value="owner" <?php echo (isset($user['role']) && $user['role'] === 'owner' ? 'selected' : ''); ?>>Owner</option>
                    </select>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn"><?php echo $user ? 'Perbarui' : 'Simpan'; ?></button>
                    <a href="users.php" class="btn btn-cancel">Batal</a>
                </div>
            </form>
        </div>
    </div>
    <?php
}
?>