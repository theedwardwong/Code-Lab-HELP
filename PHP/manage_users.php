<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$admin_name = $_SESSION['full_name'];

// Get filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$role_filter = isset($_GET['role']) ? $_GET['role'] : 'all';

// Build query
$where_conditions = ["1=1"];
$params = [];
$types = "";

if (!empty($search)) {
    $where_conditions[] = "(full_name LIKE ? OR email LIKE ?)";
    $search_param = '%' . $search . '%';
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

if ($role_filter !== 'all') {
    $where_conditions[] = "role = ?";
    $params[] = $role_filter;
    $types .= "s";
}

$where_clause = implode(" AND ", $where_conditions);

// Get all users
$users_query = "
    SELECT 
        u.*,
        (SELECT COUNT(*) FROM exercise_submissions es WHERE es.student_id = u.id) as submission_count,
        (SELECT COUNT(*) FROM assignments a WHERE a.instructor_id = u.id) as assignment_count
    FROM users u
    WHERE $where_clause
    ORDER BY u.created_at DESC
";

$users_stmt = $conn->prepare($users_query);
if (!empty($params)) {
    $users_stmt->bind_param($types, ...$params);
}
$users_stmt->execute();
$users = $users_stmt->get_result();

// Get statistics
$stats_query = "
    SELECT 
        COUNT(*) as total_users,
        COUNT(CASE WHEN role = 'student' THEN 1 END) as total_students,
        COUNT(CASE WHEN role = 'instructor' THEN 1 END) as total_instructors,
        COUNT(CASE WHEN role = 'admin' THEN 1 END) as total_admins,
        COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as new_users
    FROM users
";
$stats = $conn->query($stats_query)->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Code Lab @ HELP</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #2e3f54;
            color: white;
        }

        .navbar {
            background-color: #111;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo { color: white; font-weight: bold; font-size: 1.2rem; }
        .logo a { color: white; text-decoration: none; }
        .nav-links { list-style: none; display: flex; gap: 1.5rem; margin: 0; padding: 0; }
        .nav-links li a { color: white; text-decoration: none; }
        .nav-icons { display: flex; align-items: center; gap: 1rem; }
        
        .logout-btn {
            background-color: #2e3f54;
            color: white;
            border: none;
            padding: 0.4rem 1rem;
            border-radius: 5px;
            cursor: pointer;
        }

        .container {
            max-width: 1600px;
            margin: 0 auto;
            padding: 2rem;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .page-header h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background-color: #1a2332;
            padding: 1.5rem;
            border-radius: 12px;
            text-align: center;
            border-left: 4px solid #358efb;
        }

        .stat-value {
            font-size: 2.5rem;
            font-weight: bold;
            color: #358efb;
        }

        .stat-label {
            color: #aaa;
            margin-top: 0.5rem;
            font-size: 0.9rem;
        }

        .stat-card.students { border-left-color: #4caf50; }
        .stat-card.students .stat-value { color: #4caf50; }

        .stat-card.instructors { border-left-color: #ff9800; }
        .stat-card.instructors .stat-value { color: #ff9800; }

        .stat-card.admins { border-left-color: #f44336; }
        .stat-card.admins .stat-value { color: #f44336; }

        /* Filters */
        .filters {
            background-color: #1a2332;
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
        }

        .filters input[type="text"] {
            flex: 1;
            min-width: 300px;
            padding: 0.8rem;
            border-radius: 8px;
            border: 1px solid #2e3f54;
            background-color: #2e3f54;
            color: white;
        }

        .filters select {
            padding: 0.8rem;
            border-radius: 8px;
            border: 1px solid #2e3f54;
            background-color: #2e3f54;
            color: white;
            cursor: pointer;
        }

        .btn {
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.2s;
        }

        .btn-primary {
            background-color: #358efb;
            color: white;
        }

        .btn-primary:hover {
            background-color: #2a72c9;
        }

        .btn-search {
            background-color: #4caf50;
            color: white;
        }

        .btn-search:hover {
            background-color: #45a049;
        }

        /* Users Table */
        .users-container {
            background-color: #1a2332;
            border-radius: 12px;
            overflow: hidden;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background-color: #111b25;
        }

        th {
            padding: 1rem;
            text-align: left;
            font-weight: bold;
            border-bottom: 2px solid #2e3f54;
        }

        td {
            padding: 1rem;
            border-bottom: 1px solid #2e3f54;
        }

        tbody tr:hover {
            background-color: #1f2a3a;
        }

        .role-badge {
            display: inline-block;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: bold;
        }

        .role-admin {
            background-color: #f44336;
            color: white;
        }

        .role-instructor {
            background-color: #ff9800;
            color: white;
        }

        .role-student {
            background-color: #4caf50;
            color: white;
        }

        .action-btns {
            display: flex;
            gap: 0.5rem;
        }

        .btn-action {
            padding: 0.4rem 0.8rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.85rem;
            transition: all 0.2s;
        }

        .btn-edit {
            background-color: #358efb;
            color: white;
        }

        .btn-edit:hover {
            background-color: #2a72c9;
        }

        .btn-reset {
            background-color: #ff9800;
            color: white;
        }

        .btn-reset:hover {
            background-color: #e68900;
        }

        .btn-delete {
            background-color: #f44336;
            color: white;
        }

        .btn-delete:hover {
            background-color: #d32f2f;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.8);
            z-index: 1000;
            overflow-y: auto;
        }

        .modal.active {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 2rem;
        }

        .modal-content {
            background-color: #1a2332;
            padding: 2rem;
            border-radius: 12px;
            max-width: 600px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #2e3f54;
        }

        .close-btn {
            background: none;
            border: none;
            color: white;
            font-size: 2rem;
            cursor: pointer;
            line-height: 1;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.8rem;
            border-radius: 8px;
            border: 1px solid #2e3f54;
            background-color: #2e3f54;
            color: white;
            font-family: inherit;
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 2rem;
        }

        .btn-secondary {
            background-color: #666;
            color: white;
        }

        .btn-secondary:hover {
            background-color: #555;
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            display: none;
        }

        .alert.show {
            display: block;
        }

        .alert-success {
            background-color: #4caf50;
            color: white;
        }

        .alert-error {
            background-color: #f44336;
            color: white;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #888;
        }

        .first-login-badge {
            background-color: #2196f3;
            color: white;
            padding: 0.2rem 0.5rem;
            border-radius: 12px;
            font-size: 0.75rem;
            margin-left: 0.5rem;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="logo">
            <a href="adminDashboard.php">Code Lab @ HELP</a>
        </div>
        <ul class="nav-links">
            <li><a href="adminDashboard.php">Dashboard</a></li>
            <li><a href="manage_users.php">Manage Users</a></li>
            <li><a href="registration.php">Add User</a></li>
            <li><a href="view_courses.php">Courses</a></li>
        </ul>
        <div class="nav-icons">
            <span><?php echo htmlspecialchars($admin_name); ?></span>
            <button class="logout-btn" onclick="confirmLogout()">Log Out</button>
        </div>
    </nav>

    <div class="container">
        <div class="page-header">
            <div>
                <h1>User Management</h1>
                <p style="color: #aaa;">Manage all platform users and permissions</p>
            </div>
            <a href="registration.php" class="btn btn-primary">+ Add New User</a>
        </div>

        <div id="alertMessage" class="alert"></div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['total_users']; ?></div>
                <div class="stat-label">Total Users</div>
            </div>
            <div class="stat-card students">
                <div class="stat-value"><?php echo $stats['total_students']; ?></div>
                <div class="stat-label">Students</div>
            </div>
            <div class="stat-card instructors">
                <div class="stat-value"><?php echo $stats['total_instructors']; ?></div>
                <div class="stat-label">Instructors</div>
            </div>
            <div class="stat-card admins">
                <div class="stat-value"><?php echo $stats['total_admins']; ?></div>
                <div class="stat-label">Admins</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['new_users']; ?></div>
                <div class="stat-label">New (7 days)</div>
            </div>
        </div>

        <!-- Filters -->
        <form class="filters" method="GET">
            <input type="text" 
                   name="search" 
                   placeholder="Search by name or email..." 
                   value="<?php echo htmlspecialchars($search); ?>">
            
            <select name="role" onchange="this.form.submit()">
                <option value="all" <?php echo $role_filter === 'all' ? 'selected' : ''; ?>>All Roles</option>
                <option value="student" <?php echo $role_filter === 'student' ? 'selected' : ''; ?>>Students</option>
                <option value="instructor" <?php echo $role_filter === 'instructor' ? 'selected' : ''; ?>>Instructors</option>
                <option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>>Admins</option>
            </select>

            <button type="submit" class="btn btn-search">Search</button>
            <a href="manage_users.php" class="btn btn-secondary">Clear</a>
        </form>

        <!-- Users Table -->
        <div class="users-container">
            <?php if ($users->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Activity</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($user = $users->fetch_assoc()): ?>
                            <tr>
                                <td><strong>#<?php echo $user['id']; ?></strong></td>
                                <td>
                                    <?php echo htmlspecialchars($user['full_name']); ?>
                                    <?php if ($user['first_login'] == 1): ?>
                                        <span class="first-login-badge">First Login</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <span class="role-badge role-<?php echo $user['role']; ?>">
                                        <?php echo strtoupper($user['role']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($user['role'] === 'student'): ?>
                                        <?php echo $user['submission_count']; ?> submissions
                                    <?php elseif ($user['role'] === 'instructor'): ?>
                                        <?php echo $user['assignment_count']; ?> assignments
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <div class="action-btns">
                                        <button class="btn-action btn-edit" 
                                                data-user='<?php echo htmlspecialchars(json_encode($user), ENT_QUOTES); ?>'
                                                onclick="openEditModal(this)">
                                            ✏️ Edit
                                        </button>
                                        <button class="btn-action btn-reset" 
                                                data-user-id="<?php echo $user['id']; ?>"
                                                data-user-name="<?php echo htmlspecialchars($user['full_name']); ?>"
                                                onclick="resetPassword(this)">
                                            🔑 Reset
                                        </button>
                                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                            <button class="btn-action btn-delete" 
                                                    data-user-id="<?php echo $user['id']; ?>"
                                                    data-user-name="<?php echo htmlspecialchars($user['full_name']); ?>"
                                                    onclick="deleteUser(this)">
                                                🗑️ Delete
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <h3>No users found</h3>
                    <p>Try adjusting your search filters.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal" id="editModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit User</h2>
                <button class="close-btn" onclick="closeModal()">&times;</button>
            </div>
            <form id="editUserForm" onsubmit="submitEditUser(event)">
                <input type="hidden" id="edit_user_id" name="user_id">
                
                <div class="form-group">
                    <label>Full Name <span style="color: #f44336;">*</span></label>
                    <input type="text" id="edit_full_name" name="full_name" required>
                </div>

                <div class="form-group">
                    <label>Email <span style="color: #f44336;">*</span></label>
                    <input type="email" id="edit_email" name="email" required>
                </div>

                <div class="form-group">
                    <label>Role <span style="color: #f44336;">*</span></label>
                    <select id="edit_role" name="role" required>
                        <option value="student">Student</option>
                        <option value="instructor">Instructor</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function confirmLogout() {
            if (confirm("Are you sure you want to log out?")) {
                window.location.href = 'logout.php';
            }
        }

        function openEditModal(button) {
            const user = JSON.parse(button.getAttribute('data-user'));
            
            document.getElementById('edit_user_id').value = user.id;
            document.getElementById('edit_full_name').value = user.full_name;
            document.getElementById('edit_email').value = user.email;
            document.getElementById('edit_role').value = user.role;
            
            document.getElementById('editModal').classList.add('active');
        }

        function closeModal() {
            document.getElementById('editModal').classList.remove('active');
        }

        function submitEditUser(event) {
            event.preventDefault();
            
            const formData = new FormData(event.target);
            
            fetch('update_user.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('User updated successfully!', 'success');
                    closeModal();
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showAlert('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('Failed to update user', 'error');
            });
        }

        function resetPassword(button) {
            const userId = button.getAttribute('data-user-id');
            const userName = button.getAttribute('data-user-name');
            
            if (!confirm(`Reset password for ${userName}?\n\nA new random password will be generated.`)) {
                return;
            }
            
            fetch('reset_user_password.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ user_id: userId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(`Password reset successful!\n\nNew password: ${data.new_password}\n\nPlease save this and send it to the user.`);
                    showAlert('Password reset successfully!', 'success');
                } else {
                    showAlert('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('Failed to reset password', 'error');
            });
        }

        function deleteUser(button) {
            const userId = button.getAttribute('data-user-id');
            const userName = button.getAttribute('data-user-name');
            
            if (!confirm(`⚠️ DELETE USER: ${userName}?\n\nThis will permanently delete:\n- User account\n- All submissions\n- All assignments\n- All progress data\n\nThis action CANNOT be undone!`)) {
                return;
            }
            
            if (!confirm('Are you ABSOLUTELY SURE? This is irreversible!')) {
                return;
            }
            
            fetch('delete_user.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ user_id: userId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('User deleted successfully!', 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showAlert('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('Failed to delete user', 'error');
            });
        }

        function showAlert(message, type) {
            const alert = document.getElementById('alertMessage');
            alert.textContent = message;
            alert.className = 'alert alert-' + type + ' show';
            
            setTimeout(() => {
                alert.classList.remove('show');
            }, 5000);
        }

        window.onclick = function(event) {
            const modal = document.getElementById('editModal');
            if (event.target === modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>