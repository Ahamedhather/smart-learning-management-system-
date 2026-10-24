<?php 
include("../config/db.php");
// Assuming auth.php handles session_start() so we don't get the "session already active" error
include("../includes/auth.php"); 
include("../config/functions.php");

/* SECURITY CHECK */
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../public/login.php");
    exit();
}

$result = $conn->query("SELECT * FROM users ORDER BY user_id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Users | A too Z LMS</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

<style>
/* CORE STYLES FROM DASHBOARD */
body{
    margin:0;
    font-family:'Segoe UI', sans-serif;
    background:#f7f9ff;
    color:#111827;
}
.wrapper{
    display:flex;
}
.content{
    margin-left:290px;
    padding:35px;
    width:calc(100% - 290px);
    transition:all .3s ease;
}
.content.expanded{
    margin-left:105px;
    width:calc(100% - 105px);
}

/* TOPBAR */
.topbar{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:25px;
    gap:20px;
}
.left-title{
    display:flex;
    align-items:center;
    gap:15px;
}
.search-box{
    width:330px;
    border:1px solid #ddd;
    border-radius:12px;
    padding:12px 15px;
    background:white;
    outline: none;
}
.search-box:focus {
    border-color: #6d35f5;
    box-shadow: 0 0 0 3px rgba(109, 53, 245, 0.1);
}
.btn-gradient {
    background:linear-gradient(135deg,#6d35f5,#8b5cf6);
    color:white;
    padding:12px 20px;
    border-radius:12px;
    box-shadow:0 4px 12px rgba(109,53,245,0.2);
    font-weight:600;
    border:none;
    text-decoration: none;
    display: inline-block;
    transition: .3s;
}
.btn-gradient:hover {
    transform: translateY(-2px);
    color: white;
    box-shadow:0 6px 15px rgba(109,53,245,0.3);
}

/* SECTION BOX (TABLE CONTAINER) */
.section-box{
    background:white;
    border-radius:18px;
    padding:25px;
    margin-top:10px;
    box-shadow:0 6px 18px rgba(0,0,0,0.04);
}

/* CUSTOM TABLE STYLES */
.table thead th {
    background-color: #f8fafc;
    color: #4b5563;
    font-weight: 600;
    border-bottom: 2px solid #e5e7eb;
    padding: 15px;
}
.table tbody td {
    padding: 15px;
    vertical-align: middle;
    color: #374151;
    border-bottom: 1px solid #f3f4f6;
}
.table tbody tr:hover {
    background-color: #f8fafc;
}

/* CUSTOM BADGES */
.badge-soft-success {
    background-color: #dcfce7;
    color: #166534;
    padding: 6px 12px;
    border-radius: 8px;
    font-weight: 600;
}
.badge-soft-danger {
    background-color: #fee2e2;
    color: #991b1b;
    padding: 6px 12px;
    border-radius: 8px;
    font-weight: 600;
}
.badge-soft-primary {
    background-color: #ede9fe;
    color: #5b35f5;
    padding: 6px 12px;
    border-radius: 8px;
    font-weight: 600;
}

/* ACTION BUTTONS */
.btn-icon {
    width: 35px;
    height: 35px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    transition: .2s;
    text-decoration: none;
    margin-right: 5px;
}
.btn-edit { background: #f3f4f6; color: #374151; }
.btn-edit:hover { background: #e5e7eb; color: #111827; }
.btn-status { background: #fef9c3; color: #854d0e; }
.btn-status:hover { background: #fef08a; color: #713f12; }
.btn-delete { background: #fee2e2; color: #991b1b; }
.btn-delete:hover { background: #fecaca; color: #7f1d1d; }

/* RESPONSIVE */
@media(max-width:900px){
    .content, .content.expanded{
        margin-left:0;
        width:100%;
        padding:20px;
    }
    .topbar{
        flex-direction:column;
        align-items:flex-start;
    }
    .search-box{
        width:100%;
        margin-bottom: 10px;
    }
}
</style>
</head>

<body>

<div class="wrapper">

    <!-- INCLUDE YOUR SIDEBAR HERE -->
    <?php include("sidebar.php"); ?>

    <!-- MAIN CONTENT -->
    <main class="content" id="content">

        <!-- TOPBAR -->
        <div class="topbar">
            <div class="left-title">
                <div>
                    <h1 class="fw-bold mb-1">User Management</h1>
                    <p class="text-muted mb-0">View, search, and manage system users.</p>
                </div>
            </div>

            <div class="text-end">
                <input type="text" id="searchInput" class="search-box" placeholder="Search by name, email or role...">
                <div class="mt-2">
                    <a href="add_user.php" class="btn-gradient">
                        <i class="bi bi-person-plus-fill me-2"></i> Add New User
                    </a>
                </div>
            </div>
        </div>

        <!-- TABLE SECTION -->
        <div class="section-box">
            <div class="table-responsive">
                <table class="table align-middle mb-0" id="userTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($result->num_rows == 0){ ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                    No users found
                                </td>
                            </tr>
                        <?php } ?>

                        <?php while ($row = $result->fetch_assoc()) { ?>
                        <tr>
                            <td class="text-muted fw-bold">#<?= $row['user_id'] ?></td>
                            <td>
                                <div class="fw-bold text-dark"><?= htmlspecialchars($row['full_name']) ?></div>
                            </td>
                            <td class="text-muted">@<?= htmlspecialchars($row['username']) ?></td>
                            <td><?= htmlspecialchars($row['email'] ?? 'N/A') ?></td>
                            
                            <td>
                                <span class="badge-soft-primary">
                                    <i class="bi bi-person-badge"></i> <?= ucfirst(htmlspecialchars($row['role'])) ?>
                                </span>
                            </td>

                            <td>
                                <?php if ($row['status'] == 'active') { ?>
                                    <span class="badge-soft-success"><i class="bi bi-check-circle-fill me-1"></i> Active</span>
                                <?php } else { ?>
                                    <span class="badge-soft-danger"><i class="bi bi-x-circle-fill me-1"></i> Inactive</span>
                                <?php } ?>
                            </td>

                            <td class="text-center">
                                <a href="edit_user.php?id=<?= $row['user_id'] ?>" class="btn-icon btn-edit" title="Edit User">
                                    <i class="bi bi-pencil-fill"></i>
                                </a>

                                <?php if ($row['status'] == 'active') { ?>
                                    <a href="toggle_user.php?id=<?= $row['user_id'] ?>&status=inactive" class="btn-icon btn-status" title="Deactivate" onclick="return confirm('Deactivate this user?')">
                                        <i class="bi bi-pause-fill"></i>
                                    </a>
                                <?php } else { ?>
                                    <a href="toggle_user.php?id=<?= $row['user_id'] ?>&status=active" class="btn-icon btn-status" style="background: #dcfce7; color: #166534;" title="Activate" onclick="return confirm('Activate this user?')">
                                        <i class="bi bi-play-fill"></i>
                                    </a>
                                    
                                    <a href="delete_user.php?id=<?= $row['user_id'] ?>" class="btn-icon btn-delete" title="Delete Permanently" onclick="return confirm('Are you sure you want to permanently delete this user?');">
                                        <i class="bi bi-trash3-fill"></i>
                                    </a>
                                <?php } ?>
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>
</div>

<script>
// Search Functionality
document.getElementById("searchInput").addEventListener("keyup", function() {
    let value = this.value.toLowerCase();
    let rows = document.querySelectorAll("#userTable tbody tr");

    rows.forEach(function(row) {
        let text = row.innerText.toLowerCase();
        row.style.display = text.includes(value) ? "" : "none";
    });
});
</script>

</body>
</html>