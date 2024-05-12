<?php
include ('db_conn.php');
session_start();

// Check if the user is already logged in, redirect to chat interface if true
if (isset($_SESSION['user_id'])) {
    header("Location: chat_interface.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get user input
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Validate input
    if (!empty($email) && !empty($password)) {
        // Prepare SQL statement to retrieve user data
        $sql = "SELECT * FROM users WHERE email = '$email'";
        $result = $conn->query($sql);

        if ($result->num_rows == 1) {
            // User found, verify password
            $row = $result->fetch_assoc();
            if (password_verify($password, $row['password'])) {
                // Password is correct, start session and store user data
                $_SESSION['user_id'] = $row['user_id'];
                $_SESSION['name'] = $row['name'];
                $_SESSION['email'] = $row['email'];
                $_SESSION['role'] = $row['role'];

                // Redirect to chat interface
                header("Location: index.php");
                exit;
            } else {
                // Incorrect password
                $error = "Invalid email or password";
            }
        } else {
            // User not found
            $error = "Invalid email or password";
        }
    } else {
        // Empty email or password
        $error = "Email and password are required";
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
</head>

<body>
    <h2>Login</h2>
    <?php if (isset($error))
        echo "<p>$error</p>"; ?>
    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
        <label>Email:</label><br>
        <input type="text" name="email"><br>
        <label>Password:</label><br>
        <input type="password" name="password"><br><br>
        <input type="submit" value="Login">
        <p>Don't have an account? <button><a href="signup.php">SignUp</a></button></p>
    </form>
</body>

</html>