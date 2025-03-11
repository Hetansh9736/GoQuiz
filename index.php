<?php
require_once 'config.php';

$error = '';


if (isLoggedIn()) {
    redirect('dashboard.php');
}


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password.";
    } else {
        $sql = "SELECT id, name, email, password FROM users WHERE email = ?";

        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $email);

            if ($stmt->execute()) {
                $stmt->store_result();

                if ($stmt->num_rows == 1) {
                    $stmt->bind_result($id, $name, $email, $hashed_password);

                    if ($stmt->fetch()) {
                        if (password_verify($password, $hashed_password)) {
                            session_start();

                            $_SESSION['user_id'] = $id;
                            $_SESSION['user_name'] = $name;
                            $_SESSION['user_email'] = $email;

                            redirect('dashboard.php');
                        } else {
                            $error = "Invalid password.";
                        }
                    }
                } else {
                    $error = "No account found with that email.";
                }
            } else {
                $error = "Oops! Something went wrong. Please try again later.";
            }

            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Quiz App</title>
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
                <h1 class="text-3xl font-semibold text-center text-accent mb-6">Login to Quiz App</h1>

                <?php if (!empty($error)): ?>
                    <div class="bg-error-bg border border-error-border text-error-text px-4 py-3 rounded relative mb-4" role="alert">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="space-y-6">
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

                    <button type="submit"
                        class="w-full text-accent py-3 px-6 rounded-xl bg-button-primary hover:bg-button-hover hover:shadow-lg hover:shadow-secondary/50 hover:translate-y-[-2px] focus:outline-none focus:ring-2 focus:ring-secondary focus:ring-offset-2 focus:ring-offset-primary transition-all duration-300">
                        Login
                    </button>
                </form>

                <div class="mt-8 text-center text-sm text-gray-300">
                    Don't have an account?
                    <a href="register.php" class="text-link hover:underline hover:text-secondary">Register</a>
                </div>
            </div>
        </div>
    </div>
</body>

</html>