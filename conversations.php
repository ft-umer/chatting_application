<?php
// Include database connection code
include ('db_conn.php');
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Get logged-in user ID
$user_id = $_SESSION['user_id'];

// Retrieve conversation type and ID from URL
$type = $_GET['type']; // individual or group
$conversation_id = $_GET['id']; // user_id or group_id

// Handle Individual Chat Form Submission
if (isset($_REQUEST['send_message'])) {
    $receiverType = $_GET['type'];
    if ($receiverType == 'individual') {
        $receiver_id = $_GET['id'];
        $message = $_REQUEST['message'];

        // Insert message into text_messages table
        $sql_isert_message = "INSERT INTO text_messages (sender_id, receiver_id, message_text) VALUES ($user_id, $receiver_id, '$message')";

        if ($conn->query($sql_isert_message) === TRUE) {
            // Message sent successfully
            header("Location:?type=" . $type . "&id=" . $receiver_id);
            exit;
        } else {
            // Error handling
            echo "Error: " . $sql_isert_message . "<br>" . $conn->error;
        }
    } else if ($receiverType == 'group') {
        $group_id = $_GET['id'];
        $message = $_REQUEST['message'];

        // Check if the logged-in user is a member of the group
        $sql_check_membership = "SELECT * FROM group_members WHERE group_id = $group_id AND user_id = $user_id";
        $result_check_membership = $conn->query($sql_check_membership);

        if ($result_check_membership->num_rows > 0) {
            // Insert message into text_messages table
            $sql_insert_group = "INSERT INTO text_messages (sender_id, group_id, message_text) VALUES ($user_id, $group_id, '$message')";
            if ($conn->query($sql_insert_group) === TRUE) {
                // Message sent successfully
                header("Location:?type=" . $type . "&id=" . $group_id);
                exit;
            } else {
                // Error handling
                echo "Error: " . $sql_insert_group . "<br>" . $conn->error;
            }
        } else {
            // User is not a member of the group
            echo "You are not a member of this group.";
        }
    }
}

// Fetch conversation details based on type
if ($type === 'individual') {
    // Fetch user details
    $sql_user = "SELECT name FROM users WHERE user_id = ?";
    $stmt_user = $conn->prepare($sql_user);
    $stmt_user->bind_param("i", $conversation_id);
    $stmt_user->execute();
    $result_user = $stmt_user->get_result();
    $user_name = $result_user->fetch_assoc()['name'];
} elseif ($type === 'group') {
    // Fetch group details
    $sql_group = "SELECT group_name FROM groups WHERE group_id = ?";
    $stmt_group = $conn->prepare($sql_group);
    $stmt_group->bind_param("i", $conversation_id);
    $stmt_group->execute();
    $result_group = $stmt_group->get_result();
    $group_name = $result_group->fetch_assoc()['group_name'];
}

// Fetch messages for the conversation
$sql_messages = "SELECT * FROM text_messages WHERE ";
if ($type === 'individual') {
    $sql_messages .= "(sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)";
} elseif ($type === 'group') {
    $sql_messages .= "group_id = ?";
}
$sql_messages .= " ORDER BY sent_at ASC";

$stmt_messages = $conn->prepare($sql_messages);

if ($type === 'individual') {
    $stmt_messages->bind_param("iiii", $user_id, $conversation_id, $conversation_id, $user_id);
} elseif ($type === 'group') {
    $stmt_messages->bind_param("i", $conversation_id);
}

$stmt_messages->execute();
$result_messages = $stmt_messages->get_result();

// Store messages in an array

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conversation</title>
    <style>
        .container {
            display: flex;
        }

        .left {
            flex: 1;
        }

        .right {
            flex: 1;
        }
    </style>
</head>

<body>
    <a href="index.php">Back</a>
    <h2>Conversation</h2>

    <div class="container">
        <!-- Left side - Individ

        <!-- Right side - Group chats -->
        <div class="right">
            <ul>
                <?php

                if ($type === 'individual') {
                    echo "<li><strong>$user_name</strong><small> - User</small></li>";
                    // Display individual chat messages
                    while ($row = $result_messages->fetch_assoc()) {
                        $sender_id = $row['sender_id'];
                        $sql1 = "SELECT * from users where user_id = $sender_id";
                        $result1 = $conn->query($sql1);
                        $row1 = $result1->fetch_assoc();
                        echo "<li><i>" . $row1['name'] . "</i> " . $row['message_text'] . "</li>";
                    }
                } else if ($type === 'group') {
                    echo "<li><strong>$group_name</strong><small> - Group</small></li>";
                    // Display group chat messages
                    while ($row = $result_messages->fetch_assoc()) {
                        $sender_id = $row['sender_id'];
                        $sql1 = "SELECT * from users where user_id = $sender_id";
                        $result1 = $conn->query($sql1);
                        $row1 = $result1->fetch_assoc();
                        echo "<li><i>" . $row1['name'] . "</i> " . $row['message_text'] . "</li>";
                    }
                }
                ?>
            </ul>
            <form method="POST"
                action="?type=<?php echo $type = ($_GET['type'] == 'group') ? 'group' : 'individual';
                echo '&id=';
                echo $id = $_GET['id']; ?>">
                <input type="text" name="message" placeholder="Enter message">
                <input type="submit" value="Send" name="send_message">
            </form>
        </div>
    </div>
</body>

</html>