<?php
// Include database connection code
include ('db_conn.php');
session_start();

// Check if the user is logged in, redirect to login page if not
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Get logged-in user ID
$user_id = $_SESSION['user_id'];




if (isset($_POST['create_group'])) {
    $group_name = $_POST['group_name'];
    $users = $_POST['users'];

    // Add the logged-in user to the list of selected users
    $users[] = $_SESSION['user_id'];

    // Get current datetime for created_at
    $created_at = date("Y-m-d H:i:s");
    $created_by = $_SESSION['user_id'];

    // Insert group into groups table
    $sql_insert_group = "INSERT INTO groups (group_name, created_by, created_at) VALUES ('$group_name', $created_by, '$created_at')";
    if ($conn->query($sql_insert_group) === TRUE) {

        // Get the ID of the newly inserted group
        $group_id = $conn->insert_id;

        // Insert group members into group_members table
        foreach ($users as $user_id) {
            $sql_insert_member = "INSERT INTO group_members (group_id, user_id) VALUES ($group_id, $user_id)";
            $conn->query($sql_insert_member);
        }
    } else {
        // Error handling
        echo "Error: " . $sql_insert_group . "<br>" . $conn->error;
    }
}





// Get logged-in user data from session
$user_id = $_SESSION['user_id'];
$name = $_SESSION['name'];


?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat Interface</title>
</head>

<body>
    <h2>Welcome, <?php echo $name; ?></h2>
    <!-- Logout Link -->
    <a href="logout.php">Logout</a>

    <!-- Individual Chat Section -->
    <h3>Individual Conversations:</h3>
    <ul>
        <?php
        // Fetch individual conversations
        $sql_individual = "SELECT DISTINCT LEAST(sender_id, receiver_id) AS user1, GREATEST(sender_id, receiver_id) AS user2
                   FROM text_messages
                   WHERE sender_id = ? OR receiver_id = ?";
        $stmt_individual = $conn->prepare($sql_individual);
        $stmt_individual->bind_param("ii", $user_id, $user_id);
        $stmt_individual->execute();
        $result_individual = $stmt_individual->get_result();

        while ($row = $result_individual->fetch_assoc()) {
            $user1 = $row['user1'];
            $user2 = $row['user2'];

            // Determine the conversation partner
            $conversation_partner_id = $user1 == $user_id ? $user2 : $user1;

            // Fetch the name of the conversation partner and last message
            $sql_partner_last_message = "SELECT u.name AS partner_name, m.message_text AS last_message_text
                                  FROM users u
                                  JOIN (
                                      SELECT message_text, MAX(sent_at) AS max_sent_at
                                      FROM text_messages
                                      WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)
                                  ) AS m ON 1=1
                                  WHERE u.user_id = ?
                                  ORDER BY m.max_sent_at DESC
                                  LIMIT 1";
            $stmt_partner_last_message = $conn->prepare($sql_partner_last_message);
            $stmt_partner_last_message->bind_param("iiiii", $user_id, $conversation_partner_id, $conversation_partner_id, $user_id, $conversation_partner_id);
            $stmt_partner_last_message->execute();
            $result_partner_last_message = $stmt_partner_last_message->get_result();
            $row_partner_last_message = $result_partner_last_message->fetch_assoc();

            if ($row_partner_last_message) {
                $partner_name = $row_partner_last_message['partner_name'];
                $last_message_text = $row_partner_last_message['last_message_text'];

                echo "<li><a href='conversations.php?type=individual&id=$conversation_partner_id'> to $partner_name - Last Message: $last_message_text</a></li>";
            }
        }



        ?>
    </ul>

    <!-- Group Chat Section -->
    <h3>Group Conversations:</h3>
    <ul>
        <?php

        // Fetch group conversations
        $user_id = $_SESSION['user_id'];

        $sql_group_messages = "SELECT g.group_id, g.group_name, last_message.last_message_text AS last_message_text, last_message.last_message_time AS last_message_time
                      FROM groups g
                      INNER JOIN group_members gm ON g.group_id = gm.group_id
                      LEFT JOIN (
                          SELECT group_id, message_text AS last_message_text, sent_at AS last_message_time
                          FROM text_messages
                          WHERE (group_id, sent_at) IN (
                              SELECT group_id, MAX(sent_at) AS last_sent_at
                              FROM text_messages
                              GROUP BY group_id
                          )
                      ) AS last_message ON g.group_id = last_message.group_id
                      WHERE gm.user_id = $user_id
                      ORDER BY last_message.last_message_time DESC";


        $result_group_messages = $conn->query($sql_group_messages);

        while ($row = $result_group_messages->fetch_assoc()) {
            $group_id = $row['group_id'];
            $group_name = $row['group_name'];
            $last_message_text = $row['last_message_text'];
            $last_message_time = $row['last_message_time'];

            echo "<li><a href='conversations.php?type=group&id=$group_id'>$group_name - Last Message: $last_message_text (Sent at: $last_message_time)</a></li>";
        }


        ?>
    </ul>
    <!-- Individual Chat Form -->
    <h3>Start Individual Chat</h3>
    <form method="get" action="conversations.php">
        <label>Select user to chat with:</label>
        <input type="hidden" name="type" value="individual">
        <select name="id">
            <?php
            $sql_users = "SELECT user_id, name FROM users WHERE user_id != $user_id";
            $result_users = $conn->query($sql_users);
            while ($row = $result_users->fetch_assoc()) {
                echo "<option value='" . $row['user_id'] . "'>" . $row['name'] . "</option>";
                $receiverID = $row['user_id'];
            }
            ?>
        </select>
        <input type="text" name="message" placeholder="Enter message">
        <input type="submit" value="Send" name="send_message">
    </form>

    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Create Group</title>
    </head>

    <body>
        <h2>Create Group</h2>
        <form method="post">
            <label for="group_name">Group Name:</label>
            <input type="text" id="group_name" name="group_name" required><br><br>

            <label for="users">Add Users:</label><br>
            <?php
            // Fetch users excluding the logged-in user
            $sql_users = "SELECT user_id, name FROM users WHERE user_id != $user_id";
            $result_users = $conn->query($sql_users);

            // Display checkboxes for each user
            while ($row = $result_users->fetch_assoc()) {
                echo "<input type='checkbox' id='user_" . $row['user_id'] . "' name='users[]' value='" . $row['user_id'] . "'>";
                echo "<label for='user_" . $row['user_id'] . "'>" . $row['name'] . "</label><br>";
            }
            ?>

            <br>
            <input type="submit" value="Create Group" name="create_group">
        </form>
    </body>

    </html>


    <!-- Group Chat Form -->
    <h3>Start Group Chat</h3>
    <form method="get" action="conversations.php">
        <label>Select group to chat with:</label>

        <input type="hidden" name="type" value="group">
        <select name="id">
            <?php
            // Fetch groups where the logged-in user is a member
            $sql_groups = "SELECT g.group_id, g.group_name 
               FROM groups g 
               INNER JOIN group_members gm ON g.group_id = gm.group_id 
               WHERE gm.user_id = $user_id";
            $result_groups = $conn->query($sql_groups);

            while ($row = $result_groups->fetch_assoc()) {
                echo "<option value='" . $row['group_id'] . "'>" . $row['group_name'] . "</option>";
            }
            ?>

        </select>
        <input type="text" name="message" placeholder="Enter message">
        <input type="submit" value="Send" name="send_message">
    </form>
</body>

</html>