<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include("../config/db.php");
include("../config/functions.php");

// Fetch Attendance Aggregates grouped by Course
$result = $conn->query("
    SELECT 
        c.CourseTitle, 
        COUNT(*) as total,
        SUM(CASE WHEN a.Status = 'Present' THEN 1 ELSE 0 END) as present
    FROM Attendance a
    JOIN Course c ON a.CourseID = c.CourseID
    GROUP BY a.CourseID, c.CourseTitle
    ORDER BY c.CourseTitle ASC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Attendance Report | LMS</title>
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

.progress {
    height: 8px;
    border-radius: 10px;
    background-color: #e2e8f0;
}

@media print {
    .sidebar, .topbar, #searchInput, .btn-print {
        display: none !important;
    }
    .content {
        margin-left: 0 !important;
        width: 100% !important;
        padding: 0 !important;
    }
    .section-box {
        box-shadow: none !important;
        padding: 0 !important;
    }
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
                    <h1 class="fw-bold mb-1">Attendance Report</h1>
                    <p class="text-muted mb-0">Course-level breakdown of student attendance ratios and records.</p>
                </div>
            </div>
            <div class="d-flex gap-2">
                <button onclick="window.print()" class="btn btn-outline-primary rounded-3 px-3 btn-print">
                    <i class="bi bi-printer me-1"></i> Print Report
                </button>
                <!-- <a href="dashboard.php" class="btn btn-outline-secondary rounded-3 px-3">
                    <i class="bi bi-house me-1"></i> Dashboard
                </a> -->
            </div>
        </div>

        <!-- REPORT TABLE CONTAINER -->
        <div class="section-box">

            <!-- SEARCH TOOLBAR -->
            <div class="row mb-4">
                <div class="col-md-6 col-lg-4">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0">
                            <i class="bi bi-search text-muted"></i>
                        </span>
                        <input type="text" id="searchInput" class="form-control border-start-0 search-input" placeholder="Search by course title...">
                    </div>
                </div>
            </div>

            <!-- TABLE DATA -->
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="reportTable">
                    <thead>
                        <tr>
                            <th scope="col">Course Title</th>
                            <th scope="col" class="text-center">Total Records</th>
                            <th scope="col" class="text-center">Present</th>
                            <th scope="col" class="text-center">Absent</th>
                            <th scope="col" style="min-width: 220px;">Attendance Rate</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($result && $result->num_rows > 0){ ?>
                            <?php while($row = $result->fetch_assoc()) { 
                                $total = intval($row['total']);
                                $present = intval($row['present']);
                                $absent = $total - $present;
                                $percent = ($total > 0) ? round(($present / $total) * 100, 1) : 0;

                                // Badge & Progress Bar color styling based on percentage threshold
                                if ($percent >= 75) {
                                    $status_class = "bg-success";
                                    $badge_class = "bg-success-subtle text-success border border-success-subtle";
                                } elseif ($percent >= 50) {
                                    $status_class = "bg-warning";
                                    $badge_class = "bg-warning-subtle text-warning-emphasis border border-warning-subtle";
                                } else {
                                    $status_class = "bg-danger";
                                    $badge_class = "bg-danger-subtle text-danger border border-danger-subtle";
                                }
                            ?>
                                <tr>
                                    <td>
                                        <div class="fw-semibold text-dark">
                                            <i class="bi bi-journal-bookmark me-2 text-primary"></i>
                                            <?= htmlspecialchars($row['CourseTitle']) ?>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-dark border px-2 py-1"><?= $total ?></span>
                                    </td>
                                    <td class="text-center">
                                        <span class="text-success fw-semibold"><i class="bi bi-check-circle me-1"></i><?= $present ?></span>
                                    </td>
                                    <td class="text-center">
                                        <span class="text-danger fw-semibold"><i class="bi bi-x-circle me-1"></i><?= $absent ?></span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="progress flex-grow-1">
                                                <div class="progress-bar <?= $status_class ?>" role="progressbar" style="width: <?= $percent ?>%;" aria-valuenow="<?= $percent ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                            <span class="badge <?= $badge_class ?> rounded-pill" style="min-width: 55px;">
                                                <?= $percent ?>%
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                            <?php } ?>
                        <?php } else { ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    <i class="bi bi-bar-chart fs-3 d-block mb-2 text-secondary"></i>
                                    No attendance records found.
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
    let rows = document.querySelectorAll("#reportTable tbody tr");

    rows.forEach(function(row) {
        if (row.children.length === 1) return; // Skip "No records found" row
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