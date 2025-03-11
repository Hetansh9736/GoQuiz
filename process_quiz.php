<?php
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('index.php');
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] != 'POST' || !isset($_POST['quiz_id'])) {
    redirect('dashboard.php');
}

$quiz_id = (int)$_POST['quiz_id'];

if (!isset($_SESSION['current_quiz']) || !isset($_SESSION['quiz_questions'])) {
    redirect('dashboard.php');
}

$quiz = $_SESSION['current_quiz'];
$questions = $_SESSION['quiz_questions'];

$total_questions = count($questions);
$correct_answers = 0;

if (isset($_POST['answers']) && is_array($_POST['answers'])) {
    foreach ($_POST['answers'] as $question_id => $selected_option_id) {
        foreach ($questions as $question) {
            if ($question['id'] == $question_id) {
                foreach ($question['options'] as $option) {
                    if ($option['id'] == $selected_option_id && $option['is_correct']) {
                        $correct_answers++;
                        break;
                    }
                }
                break;
            }
        }
    }
}

$score = $total_questions > 0 ? round(($correct_answers / $total_questions) * 100) : 0;

$sql = "INSERT INTO quiz_results (quiz_id, user_id, score) VALUES (?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $quiz_id, $user_id, $score);
$stmt->execute();

unset($_SESSION['current_quiz']);
unset($_SESSION['quiz_questions']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Results - Quiz App</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
                        'link': '#48D1CC',
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
<body class="bg-primary min-h-screen flex items-center justify-center p-4 font-poppins">
    <div class="w-full max-w-md">
        <div class="bg-input-bg bg-opacity-75 backdrop-blur-lg rounded-3xl shadow-lg overflow-hidden animate-fadeIn">
            <div class="p-6 text-center">
                <h1 class="text-2xl font-bold mb-2 text-accent">Quiz Results</h1>
                <h2 class="text-xl mb-6 text-accent"><?php echo htmlspecialchars($quiz['title']); ?></h2>
                
                <div class="mb-8">
                    <div class="text-5xl font-bold <?php echo $score >= 70 ? 'text-green-600' : ($score >= 40 ? 'text-yellow-600' : 'text-red-600'); ?>">
                        <?php echo $score; ?>%
                    </div>
                    <p class="text-gray-400 mt-2">
                        You got <?php echo $correct_answers; ?> out of <?php echo $total_questions; ?> questions correct
                    </p>
                </div>
                
                <div class="space-y-4 mb-8">
                    <div class="flex justify-between">
                        <span class="text-gray-400">Total Questions:</span>
                        <span class="font-medium text-accent"><?php echo $total_questions; ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400">Correct Answers:</span>
                        <span class="font-medium text-green-600"><?php echo $correct_answers; ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400">Incorrect Answers:</span>
                        <span class="font-medium text-red-600"><?php echo $total_questions - $correct_answers; ?></span>
                    </div>
                </div>
                
                <div class="flex flex-col space-y-3">
                    <a href="dashboard.php" class="bg-button-primary text-accent py-2 px-4 rounded-md hover:bg-button-hover focus:outline-none focus:ring-2 focus:ring-secondary focus:ring-offset-2 transition-all duration-300">
                        Return to Dashboard
                    </a>
                    <a href="take_quiz.php?id=<?php echo $quiz_id; ?>" class="text-link hover:underline">
                        Take Quiz Again
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>         