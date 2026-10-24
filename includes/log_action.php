<?php
function logAction($conn, $user_id, $action) {
    $stmt = $conn->prepare("INSERT INTO ActivityLog (UserID, Action) VALUES (?, ?)");
    $stmt->bind_param("is", $user_id, $action);
    $stmt->execute();
}
?>