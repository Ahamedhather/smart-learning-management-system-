<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include("../config/db.php");

// Determine user ID based on who is logged in
$my_id = $_SESSION['user_id'] ?? $_SESSION['student_id'] ?? $_SESSION['user']['user_id'] ?? null;

if (!$my_id) {
    header("Location: ../login.php");
    exit();
}

// Fetch user role to determine which sidebar template/role context to use if needed
$user_role = $_SESSION['role'] ?? 'student';

// Handle sending a new message via POST (using table 'message')
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $receiver_id = intval($_POST['receiver_id']);
    $message_text = trim($_POST['message_text']);

    if (!empty($message_text) && $receiver_id > 0) {
        $stmt = $conn->prepare("INSERT INTO message (SenderID, ReceiverID, MessageText, ReadStatus) VALUES (?, ?, ?, 'Unread')");
        $stmt->bind_param("iis", $my_id, $receiver_id, $message_text);
        $stmt->execute();
    }
    header("Location: chat.php?user=" . $receiver_id);
    exit();
}

// Selected chat partner ID (if any)
$chat_with = isset($_GET['user']) ? intval($_GET['user']) : 0;

// Search query handling
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

// Fetch list of available contacts along with unread message counts for each contact (with optional search filter)
if (!empty($search_query)) {
    $search_param = "%" . $search_query . "%";
    $contacts_stmt = $conn->prepare("
        SELECT u.user_id, u.full_name, u.role, 
               (SELECT COUNT(*) FROM message WHERE SenderID = u.user_id AND ReceiverID = ? AND ReadStatus = 'Unread') AS unread_count 
        FROM users u 
        WHERE u.user_id != ? AND u.full_name LIKE ? 
        ORDER BY unread_count DESC, u.full_name ASC
    ");
    $contacts_stmt->bind_param("iis", $my_id, $my_id, $search_param);
} else {
    $contacts_stmt = $conn->prepare("
        SELECT u.user_id, u.full_name, u.role, 
               (SELECT COUNT(*) FROM message WHERE SenderID = u.user_id AND ReceiverID = ? AND ReadStatus = 'Unread') AS unread_count 
        FROM users u 
        WHERE u.user_id != ? 
        ORDER BY unread_count DESC, u.full_name ASC
    ");
    $contacts_stmt->bind_param("ii", $my_id, $my_id);
}
$contacts_stmt->execute();
$contacts = $contacts_stmt->get_result();

// Fetch conversation history if a contact is selected
$messages = [];
$chat_user = null;
if ($chat_with > 0) {
    // Get chat partner details
    $user_stmt = $conn->prepare("SELECT user_id, full_name, role FROM users WHERE user_id = ?");
    $user_stmt->bind_param("i", $chat_with);
    $user_stmt->execute();
    $chat_user = $user_stmt->get_result()->fetch_assoc();

    // Fetch messages between me and them (using table 'message' and 'SentDate')
    $msg_stmt = $conn->prepare("
        SELECT * FROM message 
        WHERE (SenderID = ? AND ReceiverID = ?) 
           OR (SenderID = ? AND ReceiverID = ?) 
        ORDER BY SentDate ASC
    ");
    $msg_stmt->bind_param("iiii", $my_id, $chat_with, $chat_with, $my_id);
    $msg_stmt->execute();
    $messages = $msg_stmt->get_result();

    // Mark messages as read
    $read_stmt = $conn->prepare("UPDATE message SET ReadStatus = 'Read' WHERE SenderID = ? AND ReceiverID = ?");
    $read_stmt->bind_param("ii", $chat_with, $my_id);
    $read_stmt->execute();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Messages | Smart LMS</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

<style>
body { background: #f7f9ff; font-family: 'Segoe UI', sans-serif; }
/* Main Layout Offset for Sidebar */
.main-content {
    margin-left: 290px;
    padding: 20px;
    transition: all 0.3s ease;
}
@media(max-width: 900px) {
    .main-content {
        margin-left: 0;
    }
}
.chat-container { display: flex; height: 80vh; background: white; border-radius: 18px; box-shadow: 0 6px 18px rgba(0,0,0,0.04); overflow: hidden; margin-top: 20px; }
.contacts-list { width: 320px; border-right: 1px solid #e2e8f0; display: flex; flex-direction: column; background: #fff; }
.contacts-scroll { overflow-y: auto; flex: 1; }
.contact-item { padding: 15px 20px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between; text-decoration: none; color: inherit; transition: background 0.2s; }
.contact-item:hover, .contact-item.active { background: #f8fafc; border-left: 4px solid #6d35f5; }
.chat-box { flex: 1; display: flex; flex-direction: column; background: #f8fafc; }
.chat-messages { flex: 1; padding: 20px; overflow-y: auto; display: flex; flex-direction: column; gap: 15px; }
.message-bubble { max-width: 65%; padding: 12px 16px; border-radius: 14px; font-size: 14px; line-height: 1.4; position: relative; }
.message-bubble.incoming { background: white; align-self: flex-start; border: 1px solid #e2e8f0; }
.message-bubble.outgoing { background: #6d35f5; color: white; align-self: flex-end; }
.chat-input-area { padding: 15px 20px; background: white; border-top: 1px solid #e2e8f0; display: flex; gap: 10px; }
.avatar-sm { width: 40px; height: 40px; border-radius: 50%; background: #e2e8f0; display: flex; align-items: center; justify-content: center; font-weight: bold; color: #475569; margin-right: 12px; flex-shrink: 0; }
</style>
</head>
<body>

<!-- Include Sidebar Component -->
<?php 
// Assuming student sidebar is standard here, change path if your teacher dashboard uses a separate sidebar file
include("sidebar_student.php"); 
?>

<!-- MAIN WRAPPER CONTAINER -->
<div class="main-content">
    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2><i class="bi bi-chat-dots-fill text-primary me-2"></i> Messages</h2>
            <!-- <a href="dashboard.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back to Dashboard</a> -->
        </div>

        <div class="chat-container">
            <!-- CONTACTS SIDEBAR -->
            <div class="contacts-list">
                <div class="p-3 border-bottom bg-light">
                    <form action="chat.php" method="GET" class="input-group input-group-sm">
                        <?php if ($chat_with > 0): ?>
                            <input type="hidden" name="user" value="<?= $chat_with ?>">
                        <?php endif; ?>
                        <input type="text" name="search" class="form-control" placeholder="Search user name..." value="<?= htmlspecialchars($search_query) ?>" autocomplete="off">
                        <?php if (!empty($search_query)): ?>
                            <a href="chat.php<?= $chat_with > 0 ? '?user=' . $chat_with : '' ?>" class="btn btn-outline-secondary" title="Clear Search"><i class="bi bi-x-lg"></i></a>
                        <?php endif; ?>
                        <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i></button>
                    </form>
                </div>
                
                <div class="contacts-scroll">
                    <?php if ($contacts->num_rows > 0): ?>
                        <?php while ($c = $contacts->fetch_assoc()): ?>
                            <a href="chat.php?user=<?= $c['user_id'] ?><?= !empty($search_query) ? '&search=' . urlencode($search_query) : '' ?>" class="contact-item <?= ($chat_with == $c['user_id']) ? 'active' : '' ?>">
                                <div class="d-flex align-items-center">
                                    <div class="avatar-sm"><?= strtoupper(substr($c['full_name'], 0, 1)) ?></div>
                                    <div>
                                        <div class="fw-semibold text-dark"><?= htmlspecialchars($c['full_name']) ?></div>
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary text-capitalize small" style="font-size: 10px;"><?= $c['role'] ?></span>
                                    </div>
                                </div>
                                <?php if ($c['unread_count'] > 0): ?>
                                    <span class="badge bg-danger rounded-pill"><?= $c['unread_count'] ?></span>
                                <?php endif; ?>
                            </a>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="p-4 text-center text-muted small">
                            No users found.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ACTIVE CHAT VIEW -->
            <div class="chat-box">
                <?php if ($chat_user): ?>
                    <!-- Chat Header -->
                    <div class="p-3 bg-white border-bottom d-flex align-items-center">
                        <div class="avatar-sm"><?= strtoupper(substr($chat_user['full_name'], 0, 1)) ?></div>
                        <div>
                            <h6 class="mb-0 fw-bold"><?= htmlspecialchars($chat_user['full_name']) ?></h6>
                            <span class="text-muted small text-capitalize"><?= $chat_user['role'] ?></span>
                        </div>
                    </div>

                    <!-- Messages Thread -->
                    <div class="chat-messages" id="chatMessages">
                        <?php while ($m = $messages->fetch_assoc()): ?>
                            <?php $is_outgoing = ($m['SenderID'] == $my_id); ?>
                            <div class="message-bubble <?= $is_outgoing ? 'outgoing' : 'incoming' ?>">
                                <?= nl2br(htmlspecialchars($m['MessageText'])) ?>
                                <div class="text-end mt-1" style="font-size: 9px; opacity: 0.8;">
                                    <?= date("h:i A", strtotime($m['SentDate'])) ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>

                    <!-- Input Field -->
                    <form action="chat.php?user=<?= $chat_with ?><?= !empty($search_query) ? '&search=' . urlencode($search_query) : '' ?>" method="POST" class="chat-input-area">
                        <input type="hidden" name="receiver_id" value="<?= $chat_with ?>">
                        <input type="text" name="message_text" class="form-control" placeholder="Type your message..." autocomplete="off" required>
                        <button type="submit" name="send_message" class="btn btn-primary px-4"><i class="bi bi-send-fill"></i></button>
                    </form>
                <?php else: ?>
                    <div class="d-flex flex-column align-items-center justify-content-center h-100 text-muted">
                        <i class="bi bi-chat-square-text fs-1 mb-2"></i>
                        <p>Select a contact from the left list to start messaging.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
const chatContainer = document.getElementById('chatMessages');
if (chatContainer) {
    chatContainer.scrollTop = chatContainer.scrollHeight;
}
</script>

</body>
</html>