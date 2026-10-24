<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include("../config/db.php");
include("../includes/auth.php");
include("../config/functions.php");

// Fetch Announcements with Author, Course, and Department details using correct table names (`courses`, `subjects` / `subject`, `departments` / `department`)
$result = $conn->query("
    SELECT 
        a.AnnouncementID,
        a.Title,
        a.Message,
        a.CreatedDate,
        u.full_name,
        NULL AS CourseTitle,
        NULL AS DepartmentName
    FROM Announcement a
    JOIN users u ON a.UserID = u.user_id
    ORDER BY a.CreatedDate DESC

");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Announcements | LMS</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<!-- Bootstrap 5 & Icons -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

<style>
body {
    margin: 0;
    font-family: 'Segoe UI', sans-serif;
    background: #f7f9ff;
    color: #111827;
}

.wrapper {
    display: flex;
    width: 100%;
    min-height: 100vh;
}

.content {
    margin-left: 290px;
    padding: 35px;
    width: calc(100% - 290px);
    transition: all .3s ease;
}

.content.expanded {
    margin-left: 105px;
    width: calc(100% - 105px);
}

.section-box {
    background: white;
    border-radius: 18px;
    padding: 30px;
    box-shadow: 0 6px 18px rgba(0,0,0,0.07);
}

.topbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
}

.toggle-btn {
    border: none;
    background: linear-gradient(135deg, #6d35f5, #8b5cf6);
    color: white;
    width: 45px;
    height: 45px;
    border-radius: 12px;
    font-size: 24px;
    cursor: pointer;
}

#mobileMenuToggle {
    display: none;
}

.search-input {
    border-radius: 10px;
    padding: 10px 14px;
}

.table thead th {
    background-color: #f8fafc;
    color: #475569;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.8rem;
    letter-spacing: 0.5px;
    border-bottom: 2px solid #e2e8f0;
}

.table tbody tr:hover {
    background-color: #f1f5f9;
}

.message-preview {
    max-width: 320px;
    color: #4b5563;
    font-size: 0.9rem;
}

@media(max-width:900px) {
    #mobileMenuToggle {
        display: block !important;
    }
    .content, .content.expanded {
        margin-left: 0;
        width: 100%;
        padding: 20px;
    }
}
</style>
</head>

<body>

<div class="wrapper">

    <!-- SIDEBAR INCLUSION -->
    <?php include("sidebar.php"); ?>

    <!-- MAIN CONTENT CONTAINER -->
    <main class="content" id="content">

        <!-- TOPBAR -->
        <div class="topbar">
            <div class="d-flex align-items-center gap-3">
                <button id="mobileMenuToggle" class="toggle-btn">
                    <i class="bi bi-list"></i>
                </button>
                <div>
                    <h1 class="fw-bold mb-1">Announcements</h1>
                    <p class="text-muted mb-0">Publish and manage system notifications across courses and departments.</p>
                </div>
            </div>
            <div class="d-flex gap-2">
                <a href="add_announcement.php" class="btn btn-success rounded-3 px-3">
                    <i class="bi bi-plus-circle me-1"></i> Add Announcement
                </a>
            </div>
        </div>

        <!-- ANNOUNCEMENTS TABLE CONTAINER -->
        <div class="section-box">

            <!-- SEARCH TOOLBAR -->
            <div class="row mb-4">
                <div class="col-md-6 col-lg-4">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0">
                            <i class="bi bi-search text-muted"></i>
                        </span>
                        <input type="text" id="searchInput" class="form-control border-start-0 search-input" placeholder="Search title, message, course, or author...">
                    </div>
                </div>
            </div>

            <!-- TABLE DATA -->
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="announcementTable">
                    <thead>
                        <tr>
                            <th scope="col">Title & Message</th>
                            <th scope="col">Target Scope</th>
                            <th scope="col">Posted By</th>
                            <th scope="col">Date & Time</th>
                            <th scope="col" class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($result && $result->num_rows > 0){ ?>
                            <?php while($a = $result->fetch_assoc()) { ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark mb-1">
                                            <i class="bi bi-megaphone me-1 text-primary"></i>
                                            <?= htmlspecialchars($a['Title']) ?>
                                        </div>
                                        <div class="message-preview">
                                            <?= nl2br(htmlspecialchars($a['Message'])) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column gap-1">
                                            <?php if(!empty($a['CourseTitle'])) { ?>
                                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle w-auto text-start">
                                                    <i class="bi bi-journal-bookmark me-1"></i><?= htmlspecialchars($a['CourseTitle']) ?>
                                                </span>
                                            <?php } ?>
                                            
                                            <?php if(!empty($a['DepartmentName'])) { ?>
                                                <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle w-auto text-start">
                                                    <i class="bi bi-building me-1"></i><?= htmlspecialchars($a['DepartmentName']) ?>
                                                </span>
                                            <?php } ?>

                                            <?php if(empty($a['CourseTitle']) && empty($a['DepartmentName'])) { ?>
                                                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle w-auto text-start">
                                                    <i class="bi bi-globe me-1"></i> Global / All
                                                </span>
                                            <?php } ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="fw-medium text-dark">
                                            <i class="bi bi-person me-1 text-muted"></i><?= htmlspecialchars($a['full_name']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <i class="bi bi-clock me-1"></i><?= date("d M Y, h:i A", strtotime($a['CreatedDate'])) ?>
                                        </small>
                                    </td>
                                    <td class="text-center">
                                        <a href="delete_announcement.php?id=<?= $a['AnnouncementID'] ?>" 
                                           class="btn btn-outline-danger btn-sm rounded-2 px-3"
                                           onclick="return confirm('Are you sure you want to delete this announcement?')">
                                            <i class="bi bi-trash me-1"></i> Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php } ?>
                        <?php } else { ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    <i class="bi bi-megaphone-fill fs-3 d-block mb-2 text-secondary"></i>
                                    No announcements available at this time.
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>

        </div>

    </main>
</div>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
/* REAL-TIME SEARCH FILTER */
document.getElementById("searchInput").addEventListener("keyup", function() {
    let value = this.value.toLowerCase().trim();
    let rows = document.querySelectorAll("#announcementTable tbody tr");

    rows.forEach(function(row) {
        if (row.children.length === 1) return; // Skip empty row fallback
        let text = row.innerText.toLowerCase();
        row.style.display = text.includes(value) ? "" : "none";
    });
});

/* SIDEBAR TOGGLE HANDLER */
const mobileToggleBtn = document.getElementById("mobileMenuToggle");
const sidebar = document.getElementById("sidebar");
const content = document.getElementById("content");

if (mobileToggleBtn) {
    mobileToggleBtn.addEventListener("click", () => {
        if (window.innerWidth <= 900) {
            sidebar.classList.toggle("mobile-open");
        } else {
            sidebar.classList.toggle("collapsed");
            content.classList.toggle("expanded");
        }
    });
}
</script>

</body>
</html>