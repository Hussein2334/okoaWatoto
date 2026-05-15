<?php
// admin/users.php
$page_title = 'Manage Users';
require_once '../config/database.php';
require_once 'includes/admin-header.php';

if (!isLoggedIn()) {
    header("Location: ../login.php");
    exit();
}

if ($_SESSION['user_role'] !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

// Add new user with modal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $role = $_POST['role'];
    $password = $_POST['password'];
    
    $errors = [];
    if (empty($fullname)) $errors[] = "Full name required";
    if (empty($email)) $errors[] = "Email required";
    if (empty($phone)) $errors[] = "Phone required";
    if (strlen($password) < 6) $errors[] = "Password min 6 characters";
    
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) $errors[] = "Email already exists";
    
    if (empty($errors)) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (fullname, email, phone, password, role, status) VALUES (?, ?, ?, ?, ?, 'active')");
        if ($stmt->execute([$fullname, $email, $phone, $hashed, $role])) {
            $_SESSION['success_message'] = "User added successfully!";
        }
    } else {
        $_SESSION['error_message'] = implode(", ", $errors);
    }
    header("Location: users.php");
    exit();
}

// Edit user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_user'])) {
    $user_id = intval($_POST['user_id']);
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $role = $_POST['role'];
    $status = $_POST['status'];
    $password = $_POST['password'] ?? '';
    
    if (!empty($password)) {
        if (strlen($password) < 6) {
            $_SESSION['error_message'] = "Password min 6 characters";
            header("Location: users.php");
            exit();
        }
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET fullname=?, email=?, phone=?, role=?, status=?, password=? WHERE id=?");
        $stmt->execute([$fullname, $email, $phone, $role, $status, $hashed, $user_id]);
    } else {
        $stmt = $pdo->prepare("UPDATE users SET fullname=?, email=?, phone=?, role=?, status=? WHERE id=?");
        $stmt->execute([$fullname, $email, $phone, $role, $status, $user_id]);
    }
    $_SESSION['success_message'] = "User updated successfully";
    header("Location: users.php");
    exit();
}

// Delete user
if (isset($_GET['delete'])) {
    $user_id = intval($_GET['delete']);
    if ($user_id != $_SESSION['user_id']) {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $_SESSION['success_message'] = "User deleted successfully";
    }
    header("Location: users.php");
    exit();
}

// Toggle status
if (isset($_GET['toggle'])) {
    $user_id = intval($_GET['toggle']);
    $new_status = $_GET['status'] ?? 'active';
    $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
    $stmt->execute([$new_status, $user_id]);
    $_SESSION['success_message'] = "Status updated";
    header("Location: users.php");
    exit();
}

// Get users
$search = $_GET['search'] ?? '';
$role_filter = $_GET['role'] ?? 'all';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

$sql = "SELECT * FROM users WHERE 1=1";
$params = [];
if ($role_filter != 'all') {
    $sql .= " AND role = ?";
    $params[] = $role_filter;
}
if (!empty($search)) {
    $sql .= " AND (fullname LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

$count_sql = str_replace("SELECT *", "SELECT COUNT(*) as total", $sql);
$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total_records = $stmt->fetch()['total'];
$total_pages = ceil($total_records / $per_page);

$sql .= " ORDER BY created_at DESC LIMIT $per_page OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetch()['COUNT(*)'];
$total_admin = $pdo->query("SELECT COUNT(*) FROM users WHERE role='admin'")->fetch()['COUNT(*)'];
$total_staff = $pdo->query("SELECT COUNT(*) FROM users WHERE role='staff'")->fetch()['COUNT(*)'];
$total_regular = $pdo->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetch()['COUNT(*)'];

require_once 'includes/admin-sidebar.php';
?>

<!-- Dashboard Header - Same as other pages -->
<div class="mb-4">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-3">
        <div>
            <h1 class="text-xl md:text-2xl font-bold text-primary">Manage Users</h1>
            <p class="text-gray-500 text-sm">Manage system users and roles</p>
        </div>
        <button onclick="openAddModal()" class="bg-primary text-white px-3 py-1.5 rounded-lg text-sm hover:bg-primary/90 flex items-center gap-1">
            <span class="material-symbols-outlined text-base">person_add</span>
            Add User
        </button>
    </div>
</div>

<!-- Statistics Cards - Same size as cases.php -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
    <div class="bg-white rounded-lg border border-gray-200 p-2 text-center shadow-sm">
        <div class="text-lg font-bold"><?php echo $total_users; ?></div>
        <div class="text-xs text-gray-500">Total Users</div>
    </div>
    <div class="bg-white rounded-lg border border-gray-200 p-2 text-center shadow-sm">
        <div class="text-lg font-bold text-red-600"><?php echo $total_admin; ?></div>
        <div class="text-xs text-gray-500">Admins</div>
    </div>
    <div class="bg-white rounded-lg border border-gray-200 p-2 text-center shadow-sm">
        <div class="text-lg font-bold text-blue-600"><?php echo $total_staff; ?></div>
        <div class="text-xs text-gray-500">Staff</div>
    </div>
    <div class="bg-white rounded-lg border border-gray-200 p-2 text-center shadow-sm">
        <div class="text-lg font-bold text-green-600"><?php echo $total_regular; ?></div>
        <div class="text-xs text-gray-500">Regular Users</div>
    </div>
</div>

<!-- Filters & Search - Same as cases.php -->
<div class="bg-white rounded-lg border border-gray-200 p-3 mb-4">
    <div class="flex flex-wrap gap-2 mb-3">
        <a href="?role=all" class="px-2 py-0.5 rounded-full text-xs <?php echo $role_filter == 'all' ? 'bg-primary text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'; ?>">All</a>
        <a href="?role=admin" class="px-2 py-0.5 rounded-full text-xs <?php echo $role_filter == 'admin' ? 'bg-primary text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'; ?>">Admin</a>
        <a href="?role=staff" class="px-2 py-0.5 rounded-full text-xs <?php echo $role_filter == 'staff' ? 'bg-primary text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'; ?>">Staff</a>
        <a href="?role=user" class="px-2 py-0.5 rounded-full text-xs <?php echo $role_filter == 'user' ? 'bg-primary text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'; ?>">User</a>
    </div>
    <form method="GET" class="flex gap-2">
        <input type="hidden" name="role" value="<?php echo $role_filter; ?>">
        <input type="text" name="search" placeholder="Search by name, email, or phone..." 
               value="<?php echo htmlspecialchars($search); ?>" 
               class="flex-1 px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
        <button type="submit" class="bg-primary text-white px-3 py-1.5 rounded-lg text-sm hover:bg-primary/90">Search</button>
        <?php if($search): ?>
        <a href="?role=<?php echo $role_filter; ?>" class="bg-gray-200 text-gray-700 px-3 py-1.5 rounded-lg text-sm hover:bg-gray-300">Clear</a>
        <?php endif; ?>
    </form>
</div>

<!-- Users Table - Same size as cases.php -->
<div class="bg-white rounded-lg border border-gray-200 overflow-hidden shadow-sm">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500">Name</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500">Email</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500">Phone</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500">Role</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500">Status</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php if(empty($users)): ?>
                <tr>
                    <td colspan="6" class="px-3 py-6 text-center text-gray-500 text-sm">No users found</td>
                </tr>
                <?php else: ?>
                <?php foreach($users as $user): ?>
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-3 py-2">
                        <div class="flex items-center gap-2">
                            <div class="w-6 h-6 rounded-full bg-primary/10 flex items-center justify-center">
                                <span class="material-symbols-outlined text-primary text-sm">person</span>
                            </div>
                            <span class="font-medium text-sm"><?php echo htmlspecialchars($user['fullname']); ?></span>
                        </div>
                    </td>
                    <td class="px-3 py-2 text-xs"><?php echo htmlspecialchars($user['email']); ?></td>
                    <td class="px-3 py-2 text-xs"><?php echo htmlspecialchars($user['phone']); ?></td>
                    <td class="px-3 py-2">
                        <span class="px-2 py-0.5 rounded-full text-xs font-semibold <?php 
                            echo $user['role'] == 'admin' ? 'bg-red-100 text-red-800' : ($user['role'] == 'staff' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800'); 
                        ?>">
                            <?php echo ucfirst($user['role']); ?>
                        </span>
                    </td>
                    <td class="px-3 py-2">
                        <span class="px-2 py-0.5 rounded-full text-xs font-semibold <?php 
                            echo $user['status'] == 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; 
                        ?>">
                            <?php echo ucfirst($user['status']); ?>
                        </span>
                    </td>
                    <td class="px-3 py-2">
                        <div class="flex items-center gap-1">
                            <button onclick="openEditModal(<?php echo $user['id']; ?>, '<?php echo addslashes($user['fullname']); ?>', '<?php echo $user['email']; ?>', '<?php echo $user['phone']; ?>', '<?php echo $user['role']; ?>', '<?php echo $user['status']; ?>')" 
                                    class="text-blue-600 hover:text-blue-800">
                                <span class="material-symbols-outlined text-sm">edit</span>
                            </button>
                            <?php if($user['id'] != $_SESSION['user_id']): ?>
                            <a href="?toggle=<?php echo $user['id']; ?>&status=<?php echo $user['status'] == 'active' ? 'inactive' : 'active'; ?>" 
                               class="text-yellow-600 hover:text-yellow-800 toggle-status"
                               data-name="<?php echo htmlspecialchars($user['fullname']); ?>"
                               data-status="<?php echo $user['status'] == 'active' ? 'inactive' : 'active'; ?>">
                                <span class="material-symbols-outlined text-sm"><?php echo $user['status'] == 'active' ? 'block' : 'check_circle'; ?></span>
                            </a>
                            <a href="?delete=<?php echo $user['id']; ?>" 
                               class="text-red-600 hover:text-red-800 delete-user" 
                               data-name="<?php echo htmlspecialchars($user['fullname']); ?>">
                                <span class="material-symbols-outlined text-sm">delete</span>
                            </a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Pagination - Same as cases.php -->
    <?php if($total_pages > 1): ?>
    <div class="px-3 py-2 border-t border-gray-200 flex justify-between items-center text-xs">
        <div class="text-gray-500">
            Showing <?php echo (($page - 1) * $per_page) + 1; ?> to <?php echo min($page * $per_page, $total_records); ?> of <?php echo number_format($total_records); ?> users
        </div>
        <div class="flex gap-1">
            <?php if($page > 1): ?>
            <a href="?page=<?php echo $page-1; ?>&role=<?php echo $role_filter; ?>&search=<?php echo urlencode($search); ?>" 
               class="px-2 py-1 border rounded hover:bg-gray-50">Prev</a>
            <?php endif; ?>
            
            <?php for($i = 1; $i <= min(3, $total_pages); $i++): ?>
            <a href="?page=<?php echo $i; ?>&role=<?php echo $role_filter; ?>&search=<?php echo urlencode($search); ?>" 
               class="px-2 py-1 border rounded <?php echo $page == $i ? 'bg-primary text-white border-primary' : 'hover:bg-gray-50'; ?>">
                <?php echo $i; ?>
            </a>
            <?php endfor; ?>
            
            <?php if($page < $total_pages): ?>
            <a href="?page=<?php echo $page+1; ?>&role=<?php echo $role_filter; ?>&search=<?php echo urlencode($search); ?>" 
               class="px-2 py-1 border rounded hover:bg-gray-50">Next</a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- ADD MODAL - Small popup, not full screen -->
<div id="addModal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center" onclick="closeAddModal()">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md mx-4" onclick="event.stopPropagation()">
        <div class="px-4 py-3 border-b flex justify-between items-center">
            <h2 class="text-lg font-bold text-primary">Add New User</h2>
            <button onclick="closeAddModal()" class="text-gray-400 hover:text-gray-600 text-xl">&times;</button>
        </div>
        <form method="POST" class="p-4 space-y-3">
            <input type="text" name="fullname" placeholder="Full Name" required class="w-full p-2 border rounded-lg text-sm focus:ring-2 focus:ring-primary">
            <input type="email" name="email" placeholder="Email Address" required class="w-full p-2 border rounded-lg text-sm focus:ring-2 focus:ring-primary">
            <input type="tel" name="phone" placeholder="Phone Number" required class="w-full p-2 border rounded-lg text-sm focus:ring-2 focus:ring-primary">
            <select name="role" class="w-full p-2 border rounded-lg text-sm focus:ring-2 focus:ring-primary">
                <option value="user">Regular User</option>
                <option value="staff">Staff</option>
                <option value="admin">Admin</option>
            </select>
            <input type="password" name="password" placeholder="Password (min 6 characters)" required class="w-full p-2 border rounded-lg text-sm focus:ring-2 focus:ring-primary">
            <div class="flex gap-3 pt-2">
                <button type="button" onclick="closeAddModal()" class="flex-1 bg-gray-200 text-gray-700 py-2 rounded-lg text-sm hover:bg-gray-300">Cancel</button>
                <button type="submit" name="add_user" value="1" class="flex-1 bg-primary text-white py-2 rounded-lg text-sm hover:bg-primary/90">Add User</button>
            </div>
        </form>
    </div>
</div>

<!-- EDIT MODAL - Small popup -->
<div id="editModal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center" onclick="closeEditModal()">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md mx-4" onclick="event.stopPropagation()">
        <div class="px-4 py-3 border-b flex justify-between items-center">
            <h2 class="text-lg font-bold text-primary">Edit User</h2>
            <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600 text-xl">&times;</button>
        </div>
        <form method="POST" class="p-4 space-y-3">
            <input type="hidden" name="user_id" id="edit_id">
            <input type="text" name="fullname" id="edit_fullname" placeholder="Full Name" required class="w-full p-2 border rounded-lg text-sm focus:ring-2 focus:ring-primary">
            <input type="email" name="email" id="edit_email" placeholder="Email Address" required class="w-full p-2 border rounded-lg text-sm focus:ring-2 focus:ring-primary">
            <input type="tel" name="phone" id="edit_phone" placeholder="Phone Number" required class="w-full p-2 border rounded-lg text-sm focus:ring-2 focus:ring-primary">
            <select name="role" id="edit_role" class="w-full p-2 border rounded-lg text-sm focus:ring-2 focus:ring-primary">
                <option value="user">Regular User</option>
                <option value="staff">Staff</option>
                <option value="admin">Admin</option>
            </select>
            <select name="status" id="edit_status" class="w-full p-2 border rounded-lg text-sm focus:ring-2 focus:ring-primary">
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
                <option value="suspended">Suspended</option>
            </select>
            <input type="password" name="password" placeholder="New Password (leave empty to keep current)" class="w-full p-2 border rounded-lg text-sm focus:ring-2 focus:ring-primary">
            <div class="flex gap-3 pt-2">
                <button type="button" onclick="closeEditModal()" class="flex-1 bg-gray-200 text-gray-700 py-2 rounded-lg text-sm hover:bg-gray-300">Cancel</button>
                <button type="submit" name="edit_user" value="1" class="flex-1 bg-primary text-white py-2 rounded-lg text-sm hover:bg-primary/90">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    function openAddModal() { 
        document.getElementById('addModal').classList.add('flex'); 
        document.getElementById('addModal').classList.remove('hidden'); 
    }
    function closeAddModal() { 
        document.getElementById('addModal').classList.add('hidden'); 
        document.getElementById('addModal').classList.remove('flex'); 
    }
    function openEditModal(id, name, email, phone, role, status) {
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_fullname').value = name;
        document.getElementById('edit_email').value = email;
        document.getElementById('edit_phone').value = phone;
        document.getElementById('edit_role').value = role;
        document.getElementById('edit_status').value = status;
        document.getElementById('editModal').classList.add('flex');
        document.getElementById('editModal').classList.remove('hidden');
    }
    function closeEditModal() { 
        document.getElementById('editModal').classList.add('hidden'); 
        document.getElementById('editModal').classList.remove('flex'); 
    }
    
    // Delete user confirmation
    document.querySelectorAll('.delete-user').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const url = this.href;
            const name = this.dataset.name;
            Swal.fire({
                title: 'Delete User?', 
                text: `Are you sure you want to delete ${name}?`, 
                icon: 'warning', 
                showCancelButton: true, 
                confirmButtonColor: '#dc2626', 
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Yes, Delete',
                cancelButtonText: 'Cancel'
            }).then(result => { 
                if(result.isConfirmed) window.location.href = url; 
            });
        });
    });
    
    // Toggle status confirmation
    document.querySelectorAll('.toggle-status').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const url = this.href;
            const name = this.dataset.name;
            const newStatus = this.dataset.status;
            Swal.fire({
                title: 'Change Status',
                text: `Change ${name}'s status to ${newStatus}?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#002045',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Yes, Change',
                cancelButtonText: 'Cancel'
            }).then(result => {
                if(result.isConfirmed) window.location.href = url;
            });
        });
    });
    
    <?php if(isset($_SESSION['success_message'])): ?>
    Swal.fire({ 
        icon: 'success', 
        title: 'Success', 
        text: '<?php echo addslashes($_SESSION['success_message']); ?>', 
        confirmButtonColor: '#10b981', 
        timer: 2000, 
        showConfirmButton: false 
    });
    <?php unset($_SESSION['success_message']); endif; ?>
    
    <?php if(isset($_SESSION['error_message'])): ?>
    Swal.fire({ 
        icon: 'error', 
        title: 'Error', 
        text: '<?php echo addslashes($_SESSION['error_message']); ?>', 
        confirmButtonColor: '#dc2626' 
    });
    <?php unset($_SESSION['error_message']); endif; ?>
</script>

<?php require_once 'includes/admin-footer.php'; ?>