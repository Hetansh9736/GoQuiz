<?php
require_once 'config.php';

$error = '';
$success = '';

if (isLoggedIn()) {
    redirect('dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "Please fill all required fields.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        $sql = "SELECT id FROM users WHERE email = ?";

        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $email);

            if ($stmt->execute()) {
                $stmt->store_result();

                if ($stmt->num_rows > 0) {
                    $error = "This email is already registered.";
                } else {
                    $stmt->close();

                    $sql = "INSERT INTO users (name, email, password) VALUES (?, ?, ?)";

                    if ($stmt = $conn->prepare($sql)) {
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                        $stmt->bind_param("sss", $name, $email, $hashed_password);

                        if ($stmt->execute()) {
                            $success = "Registration successful! You can now login.";
                            header("refresh:2;url=login.php");
                        } else {
                            $error = "Something went wrong. Please try again later.";
                        }

                        $stmt->close();
                    }
                }
            } else {
                $error = "Oops! Something went wrong. Please try again later.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Quiz App</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        poppins: ['Poppins', 'sans-serif'],
                    },
                    colors: {
                        'primary': '#292D3E',
                        'secondary': '#1B9AAA',
                        'accent': '#F2F2F2',
                        'input-bg': '#3B415A',
                        'input-border': '#5A6782',
                        'button-primary': '#1B9AAA',
                        'button-hover': '#168292',
                        'error-bg': '#F8D7DA',
                        'error-text': '#842029',
                        'error-border': '#F5C6CB',
                        'success-bg': '#D1F2EB',
                        'success-text': '#155724',
                        'success-border': '#C3E6CB',
                        'link': '#48D1CC'
                    },
                    animation: {
                        fadeIn: 'fadeIn 1.2s ease-out forwards',
                    },
                    keyframes: {
                        fadeIn: {
                            '0%': {
                                opacity: '0',
                                transform: 'translateY(-20px)'
                            },
                            '100%': {
                                opacity: '1',
                                transform: 'translateY(0)'
                            },
                        },
                    },
                },
            },
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>

<body class="min-h-screen flex items-center justify-center p-4 bg-primary font-poppins">
    <div class="w-full max-w-md">
        <div class="bg-input-bg bg-opacity-75 backdrop-blur-lg rounded-3xl shadow-lg overflow-hidden animate-fadeIn">
            <div class="px-8 py-10">
                <h1 class="text-3xl font-semibold text-center text-accent mb-6">Create an Account</h1>

                <?php if (!empty($error)): ?>
                    <div class="bg-error-bg border border-error-border text-error-text px-4 py-3 rounded relative mb-4" role="alert">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <div class="bg-success-bg border border-success-border text-success-text px-4 py-3 rounded relative mb-4" role="alert">
                        <?php echo $success; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="space-y-6">
                    <div>
                        <label for="name" class="block text-sm font-medium text-accent mb-2">Full Name</label>
                        <input type="text" id="name" name="name" required
                            class="w-full px-4 py-3 bg-input-bg border border-input-border rounded-xl text-accent placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary transition-all duration-300">
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-accent mb-2">Email</label>
                        <input type="email" id="email" name="email" required
                            class="w-full px-4 py-3 bg-input-bg border border-input-border rounded-xl text-accent placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary transition-all duration-300">
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-accent mb-2">Password</label>
                        <input type="password" id="password" name="password" required
                            class="w-full px-4 py-3 bg-input-bg border border-input-border rounded-xl text-accent placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary transition-all duration-300">
                    </div>

                    <div>
                        <label for="confirm_password" class="block text-sm font-medium text-accent mb-2">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required
                            class="w-full px-4 py-3 bg-input-bg border border-input-border rounded-xl text-accent placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary transition-all duration-300">
                    </div>

                    <button type="submit"
                        class="w-full text-accent py-3 px-6 rounded-xl bg-button-primary hover:bg-button-hover hover:shadow-lg hover:shadow-secondary/50 hover:translate-y-[-2px] focus:outline-none focus:ring-2 focus:ring-secondary focus:ring-offset-2 focus:ring-offset-primary transition-all duration-300">
                        Register
                    </button>
                </form>

                <div class="mt-8 text-center text-sm text-gray-300">
                    Already have an account?
                    <a href="index.php" class="text-link hover:underline hover:text-secondary">Login</a>
                </div>
            </div>
        </div>
    </div>
</body>

</html>