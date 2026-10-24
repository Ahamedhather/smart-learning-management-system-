<?php
include("../config/db.php");
include("../config/functions.php");

$logs = $conn->query("
SELECT l.*, u.FullName 
FROM ActivityLog l
JOIN Users u ON l.UserID = u.UserID
ORDER BY l.LogDate DESC
");
?>

<h2>System Logs</h2>

<table class="table">
<tr>
<th>User</th>
<th>Action</th>
<th>Date</th>
</tr>

<?php while($log = $logs->fetch_assoc()) { ?>
<tr>
<td><?= $log['FullName'] ?></td>
<td><?= $log['Action'] ?></td>
<td><?= $log['LogDate'] ?></td>
</tr>
<?php } ?>
</table>