<?php
session_start();
include 'db_connect.php';

// Check admin access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$success = '';
$error = '';

// Handle edit user role
if (isset($_POST['edit_user'])) {
    $user_id = intval($_POST['user_id']);
    $new_role = $_POST['new_role'];
    
    $update = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
    $update->bind_param("si", $new_role, $user_id);
    
    if ($update->execute()) {
        $success = "User role updated successfully!";
    } else {
        $error = "Error updating user role.";
    }
}

// Handle delete user
if (isset($_GET['delete'])) {
    $user_id = intval($_GET['delete']);
    $delete = $conn->prepare("DELETE FROM users WHERE id = ?");
    $delete->bind_param("i", $user_id);
    if ($delete->execute()) {
        $success = "User deleted successfully!";
    } else {
        $error = "Error deleting user.";
    }
}

// Handle reset password
if (isset($_POST['reset_password'])) {
    $user_id = intval($_POST['user_id']);
    $new_password = bin2hex(random_bytes(4));
    $hashed = password_hash($new_password, PASSWORD_DEFAULT);
    
    $update = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    $update->bind_param("si", $hashed, $user_id);
    
    if ($update->execute()) {
        $success = "Password reset! New password: <strong>$new_password</strong>";
    } else {
        $error = "Error resetting password.";
    }
}

// Get statistics
$stats = [];
$result = $conn->query("SELECT COUNT(*) as count FROM users");
$stats['total_users'] = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'student'");
$stats['total_students'] = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'instructor'");
$stats['total_instructors'] = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin'");
$stats['total_admins'] = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
$stats['new_users'] = $result->fetch_assoc()['count'];

// Build search query
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';
$role_filter = isset($_GET['role_filter']) ? $_GET['role_filter'] : 'all';

$users_query = "SELECT * FROM users WHERE 1=1";

// Add search filter
if (!empty($search_term)) {
    $search_term_sql = '%' . $search_term . '%';
    $users_query .= " AND (full_name LIKE '$search_term_sql' OR email LIKE '$search_term_sql')";
}

// Add role filter
if ($role_filter !== 'all') {
    $users_query .= " AND role = '$role_filter'";
}

$users_query .= " ORDER BY created_at DESC";
$users_result = $conn->query($users_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Users - Code Lab @ HELP</title>
  <style>
    * {
      box-sizing: border-box;
    }

    body {
      margin: 0;
      font-family: 'Segoe UI', sans-serif;
      background-color: #1a2332;
      color: white;
    }

    .navbar {
      background-color: #0f1419;
      padding: 1rem 2rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .logo {
      color: white;
      font-weight: bold;
      font-size: 1.2rem;
    }

    .logo a {
      color: white;
      text-decoration: none;
    }

    .nav-links {
      list-style: none;
      display: flex;
      gap: 1.5rem;
      margin: 0;
      padding: 0;
    }

    .nav-links li a {
      color: white;
      text-decoration: none;
      padding: 0.5rem 1rem;
      border-radius: 4px;
      transition: background-color 0.3s;
    }

    .nav-links li a:hover {
      background-color: #1a2332;
    }

    .nav-icons {
      display: flex;
      align-items: center;
      gap: 1rem;
    }

    .logout-btn {
      background-color: #1a2332;
      color: white;
      border: none;
      padding: 0.4rem 1rem;
      border-radius: 5px;
      cursor: pointer;
    }

    .container {
      max-width: 1400px;
      margin: 0 auto;
      padding: 2rem;
    }

    .page-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 2rem;
    }

    .page-header h2 {
      font-size: 2rem;
      margin: 0;
    }

    .page-header p {
      color: #94a3b8;
      margin: 0.5rem 0 0 0;
    }

    .btn-add {
      padding: 0.8rem 1.5rem;
      background-color: #358efb;
      color: white;
      text-decoration: none;
      border-radius: 8px;
      font-weight: bold;
    }

    .main-box {
      background-color: #1e293b;
      border-radius: 16px;
      padding: 2.5rem;
      border: 1px solid #334155;
      box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    }

    .stats {
      display: grid;
      grid-template-columns: repeat(5, 1fr);
      gap: 1.5rem;
      margin-bottom: 2rem;
    }

    .stat-box {
      background-color: #0f172a;
      border-radius: 12px;
      padding: 1.5rem;
      text-align: center;
      border: 2px solid transparent;
    }

    .stat-box:nth-child(1) { border-color: #3b82f6; }
    .stat-box:nth-child(2) { border-color: #10b981; }
    .stat-box:nth-child(3) { border-color: #f59e0b; }
    .stat-box:nth-child(4) { border-color: #ef4444; }
    .stat-box:nth-child(5) { border-color: #8b5cf6; }

    .stat-number {
      font-size: 2.5rem;
      font-weight: bold;
      margin-bottom: 0.5rem;
    }

    .stat-box:nth-child(1) .stat-number { color: #60a5fa; }
    .stat-box:nth-child(2) .stat-number { color: #34d399; }
    .stat-box:nth-child(3) .stat-number { color: #fbbf24; }
    .stat-box:nth-child(4) .stat-number { color: #f87171; }
    .stat-box:nth-child(5) .stat-number { color: #a78bfa; }

    .stat-label {
      color: #94a3b8;
      font-size: 0.85rem;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .search-container {
      background-color: #0f172a;
      padding: 1.5rem;
      border-radius: 12px;
      margin-bottom: 2rem;
      display: flex;
      gap: 1rem;
      align-items: center;
      border: 1px solid #334155;
    }

    .search-input {
      flex: 1;
      padding: 0.8rem;
      border: 1px solid #334155;
      border-radius: 8px;
      background-color: #1e293b;
      color: white;
    }

    .search-select {
      padding: 0.8rem;
      border: 1px solid #334155;
      border-radius: 8px;
      background-color: #1e293b;
      color: white;
    }

    .btn-search {
      padding: 0.8rem 1.5rem;
      background-color: #4caf50;
      color: white;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      font-weight: bold;
    }

    .btn-clear {
      padding: 0.8rem 1.5rem;
      background-color: #6b7280;
      color: white;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      font-weight: bold;
    }

    table {
      width: 100%;
      background-color: #0f172a;
      border-radius: 12px;
      overflow: hidden;
      border-collapse: collapse;
      border: 1px solid #334155;
    }

    th {
      background-color: #0f172a;
      padding: 1rem;
      text-align: left;
      font-weight: 600;
      color: #f1f5f9;
      border-bottom: 2px solid #334155;
    }

    td {
      padding: 1rem;
      border-bottom: 1px solid #334155;
      color: #cbd5e1;
    }

    tr:last-child td {
      border-bottom: none;
    }

    tr:hover {
      background-color: #1e293b;
    }

    .role-badge {
      display: inline-block;
      padding: 0.3rem 0.8rem;
      border-radius: 12px;
      font-size: 0.85rem;
      font-weight: 600;
    }

    .role-student { background-color: #4caf50; color: white; }
    .role-instructor { background-color: #ff9800; color: white; }
    .role-admin { background-color: #f44336; color: white; }

    .first-login-badge {
      background-color: #358efb;
      color: white;
      padding: 0.2rem 0.6rem;
      border-radius: 8px;
      font-size: 0.75rem;
      margin-left: 0.5rem;
    }

    .action-btn {
      padding: 0.5rem 1rem;
      margin: 0 0.2rem;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      font-size: 0.85rem;
      transition: transform 0.2s;
      font-weight: bold;
    }

    .action-btn:hover {
      transform: translateY(-2px);
    }

    .btn-edit { background-color: #358efb; color: white; }
    .btn-reset { background-color: #ff9800; color: white; }
    .btn-delete { background-color: #f44336; color: white; }

    .alert {
      padding: 1rem;
      margin-bottom: 1.5rem;
      border-radius: 8px;
    }

    .alert-success {
      background-color: #064e3b;
      border: 1px solid #10b981;
      color: #6ee7b7;
    }

    .alert-error {
      background-color: #7f1d1d;
      border: 1px solid #ef4444;
      color: #fca5a5;
    }

    /* Modal */
    .modal {
      display: none;
      position: fixed;
      z-index: 1000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0,0,0,0.7);
    }

    .modal-content {
      background-color: #1e293b;
      margin: 10% auto;
      padding: 2rem;
      border-radius: 12px;
      width: 90%;
      max-width: 500px;
      border: 1px solid #334155;
    }

    .modal-header {
      font-size: 1.5rem;
      margin-bottom: 1.5rem;
      color: #f1f5f9;
    }

    .modal-body label {
      display: block;
      margin-bottom: 0.5rem;
      color: #e4e7eb;
    }

    .modal-body select {
      width: 100%;
      padding: 0.8rem;
      border-radius: 8px;
      border: 1px solid #334155;
      background-color: #0f172a;
      color: white;
      margin-bottom: 1.5rem;
    }

    .modal-actions {
      display: flex;
      gap: 1rem;
      justify-content: flex-end;
    }

    .btn-cancel {
      padding: 0.8rem 1.5rem;
      background-color: #6b7280;
      color: white;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      font-weight: bold;
    }

    .btn-save {
      padding: 0.8rem 1.5rem;
      background-color: #358efb;
      color: white;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      font-weight: bold;
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
      <li><a href="registration.php">Register User</a></li>
      <li><a href="create_course.php">Create Course</a></li>
      <li><a href="view_courses.php">View Courses</a></li>
      <li><a href="manage_users.php">Manage Users</a></li>
      <li><a href="system_settings.php">System Settings</a></li>
    </ul>
    <div class="nav-icons">
      <span class="icon">🔔</span>
      <span class="icon">⚙️</span>
      <span class="icon">👤</span>
      <span class="username"><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
      <button class="logout-btn" onclick="confirmLogout()">Log Out</button>
    </div>
  </nav>

  <div class="container">
    <div class="page-header">
      <div>
        <h2>User Management</h2>
        <p>Manage all platform users and permissions</p>
      </div>
      <a href="registration.php" class="btn-add">+ Add New User</a>
    </div>

    <div class="main-box">
      <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
      <?php endif; ?>

      <?php if ($error): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
      <?php endif; ?>

      <div class="stats">
        <div class="stat-box">
          <div class="stat-number"><?php echo $stats['total_users']; ?></div>
          <div class="stat-label">Total Users</div>
        </div>
        <div class="stat-box">
          <div class="stat-number"><?php echo $stats['total_students']; ?></div>
          <div class="stat-label">Students</div>
        </div>
        <div class="stat-box">
          <div class="stat-number"><?php echo $stats['total_instructors']; ?></div>
          <div class="stat-label">Instructors</div>
        </div>
        <div class="stat-box">
          <div class="stat-number"><?php echo $stats['total_admins']; ?></div>
          <div class="stat-label">Admins</div>
        </div>
        <div class="stat-box">
          <div class="stat-number"><?php echo $stats['new_users']; ?></div>
          <div class="stat-label">New (7 days)</div>
        </div>
      </div>

      <!-- Search Form -->
      <form method="GET" action="manage_users.php" class="search-container">
        <input type="text" name="search" class="search-input" 
               placeholder="Search by name or email..." 
               value="<?php echo htmlspecialchars($search_term); ?>">
        <select name="role_filter" class="search-select">
          <option value="all" <?php echo $role_filter === 'all' ? 'selected' : ''; ?>>All Roles</option>
          <option value="student" <?php echo $role_filter === 'student' ? 'selected' : ''; ?>>Student</option>
          <option value="instructor" <?php echo $role_filter === 'instructor' ? 'selected' : ''; ?>>Instructor</option>
          <option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>>Admin</option>
        </select>
        <button type="submit" class="btn-search">Search</button>
        <a href="manage_users.php" class="btn-clear" style="text-decoration: none; display: inline-block; text-align: center;">Clear</a>
      </form>

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
          <?php if ($users_result->num_rows > 0): ?>
            <?php while ($user = $users_result->fetch_assoc()): ?>
              <tr>
                <td>#<?php echo $user['id']; ?></td>
                <td>
                  <?php echo htmlspecialchars($user['full_name']); ?>
                  <?php if ($user['first_login']): ?>
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
                  <?php 
                  if ($user['role'] == 'student') {
                    echo '0 submissions';
                  } elseif ($user['role'] == 'instructor') {
                    echo '0 assignments';
                  } else {
                    echo '-';
                  }
                  ?>
                </td>
                <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                <td>
                  <button class="action-btn btn-edit" 
                          onclick="openEditModal(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['full_name'], ENT_QUOTES); ?>', '<?php echo $user['role']; ?>')">
                    ✏️ Edit
                  </button>
                  <form method="POST" style="display: inline;">
                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                    <button type="submit" name="reset_password" class="action-btn btn-reset" 
                            onclick="return confirm('Reset password for this user?')">
                      🔄 Reset
                    </button>
                  </form>
                  <button class="action-btn btn-delete" 
                          onclick="confirmDelete(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['full_name'], ENT_QUOTES); ?>')">
                    🗑️ Delete
                  </button>
                </td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr>
              <td colspan="7" style="text-align: center; padding: 2rem; color: #94a3b8;">
                No users found matching your search.
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Edit User Modal -->
  <div id="editModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">Edit User Role</div>
      <form method="POST" action="manage_users.php" class="modal-body">
        <input type="hidden" name="user_id" id="edit_user_id">
        
        <label>User Name</label>
        <input type="text" id="edit_user_name" readonly style="background-color: #0f172a; border: 1px solid #334155; padding: 0.8rem; border-radius: 8px; width: 100%; color: #94a3b8; margin-bottom: 1rem;">
        
        <label>Select New Role</label>
        <select name="new_role" id="edit_user_role">
          <option value="student">Student</option>
          <option value="instructor">Instructor</option>
          <option value="admin">Admin</option>
        </select>
        
        <div class="modal-actions">
          <button type="button" class="btn-cancel" onclick="closeEditModal()">Cancel</button>
          <button type="submit" name="edit_user" class="btn-save">Save Changes</button>
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

    function confirmDelete(userId, userName) {
      if (confirm(`Delete user "${userName}"?\n\nThis action cannot be undone.`)) {
        window.location.href = '?delete=' + userId;
      }
    }

    function openEditModal(userId, userName, currentRole) {
      document.getElementById('edit_user_id').value = userId;
      document.getElementById('edit_user_name').value = userName;
      document.getElementById('edit_user_role').value = currentRole;
      document.getElementById('editModal').style.display = 'block';
    }

    function closeEditModal() {
      document.getElementById('editModal').style.display = 'none';
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
      const modal = document.getElementById('editModal');
      if (event.target == modal) {
        closeEditModal();
      }
    }
  </script>
</body>
</html>