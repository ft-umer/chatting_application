<?php
// Include database connection
include 'db_conn.php';

// Initialize $info variable
$info = "";

// Check if the signup form is submitted
if (isset($_POST['signup'])) {
    // Retrieve form data
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Check if password matches confirm password
    if ($password !== $confirm_password) {
        $info = "<p class='alert alert-danger'>Error: Passwords do not match.</p>";
    } else {
        // Check if user with the same email already exists
        $check_query = "SELECT * FROM users WHERE email = ?";
        $stmt = $conn->prepare($check_query);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $info = "<p class='alert alert-danger'>Error: User with this email already exists.</p>";
        } else {
            // Hash the password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Insert user data into database
            $insert_query = "INSERT INTO users (name, email, password) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($insert_query);
            $stmt->bind_param("sss", $name, $email, $hashed_password);
            if ($stmt->execute()) {
                // User signed up successfully
                $info = "<p class='alert alert-success'>Registered successfully!</p>";
                header('location:login.php');
            } else {
                $info = "<p class='alert alert-danger'>Error: " . $conn->error . "</p>";
            }
        }
    }
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="Mark Otto, Jacob Thornton, and Bootstrap contributors">
    <meta name="generator" content="Hugo 0.84.0">
    <title>TeamForceConnect</title>
    <!-- Bootstrap core CSS -->
    <link rel="stylesheet" href="./assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="./assets/fontawesome/css/all.css">
    <link rel="stylesheet" href="./assets/css/style.css?v=1">
</head>

<body>
    <div class="container">
        <div class="row">
            <main class="col-lg-4 col-md-6 col-sm-8 col-12 px-md-4 mx-auto py-5">
                <div class="card">
                    <div class="card-header text-center">
                        <h5 class="card-title fw-bold">Sign Up</h5>
                    </div>
                    <div class="card-body">
                        <form class="needs-validation" novalidate method="POST" action="">
                            <?php echo $info; ?>
                            <div class="mb-3">
                                <label for="name" class="form-label">Name</label>
                                <input type="text" class="form-control bg-transparent" id="name" name="name" required>
                                <p class="invalid-feedback mb-0">Name is required!</p>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control bg-transparent" id="email" name="email"
                                    required>
                                <p class="invalid-feedback mb-0">Enter a valid Email!</p>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" minlength="4" class="form-control bg-transparent" id="password"
                                    name="password" required>
                                <p class="invalid-feedback mb-0">Password should be at least 4 characters!</p>
                            </div>
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Password</label>
                                <input type="password" minlength="4" class="form-control bg-transparent"
                                    id="confirm_password" name="confirm_password" required>
                                <p class="invalid-feedback mb-0">Password should be at least 4 characters!</p>
                            </div>
                            <div class="mb-3">
                                <p>Already have an account? <a href="login.php"
                                        class="text-primary text-decoration-none">Login Here</a></p>
                            </div>
                            <div class="text-center">
                                <button type="submit" class="btn btn-primary mx-auto" name="signup">Sign Up</button>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <script src="./assets/js/jquery-3.6.1.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
    <script src="./assets/js/bootstrap.bundle.min.js"></script>
    <script src="./assets/js/script.js"></script>
</body>

</html>