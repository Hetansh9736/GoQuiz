<?php
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('index.php');
}

$user_id = $_SESSION['user_id'];
$quiz_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($quiz_id <= 0) {
    redirect('dashboard.php');
}

$sql = "SELECT q.*, u.name as creator_name FROM quizzes q JOIN users u ON q.user_id = u.id WHERE q.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $quiz_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = "Quiz not found.";
    redirect('dashboard.php');
}

$quiz = $result->fetch_assoc();

if (!$quiz['is_public'] && $quiz['user_id'] != $user_id) {
    $_SESSION['error'] = "You don't have permission to access this quiz.";
    redirect('dashboard.php');
}

$questions = [];
$sql = "SELECT * FROM questions WHERE quiz_id = ? ORDER BY question_order";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $quiz_id);
$stmt->execute();
$result = $stmt->get_result();

while ($question = $result->fetch_assoc()) {
    // Get options for this question
    $options = [];
    $sql = "SELECT * FROM options WHERE question_id = ? ORDER BY option_order";
    $stmt2 = $conn->prepare($sql);
    $stmt2->bind_param("i", $question['id']);
    $stmt2->execute();
    $result2 = $stmt2->get_result();
    
    while ($option = $result2->fetch_assoc()) {
        $options[] = $option;
    }
    
    $question['options'] = $options;
    $questions[] = $question;
}

$_SESSION['quiz_questions'] = $questions;
$_SESSION['current_quiz'] = $quiz;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($quiz['title']); ?> - Quiz App</title>
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
<body class="bg-primary min-h-screen font-poppins">
    <div class="max-w-3xl mx-auto px-4 py-8">
        <div class="bg-input-bg bg-opacity-75 backdrop-blur-lg rounded-3xl shadow-lg overflow-hidden animate-fadeIn">
            <div class="p-6 border-b border-input-border">
                <h1 class="text-2xl font-bold text-accent"><?php echo htmlspecialchars($quiz['title']); ?></h1>
                <p class="text-gray-400 mt-1"><?php echo htmlspecialchars($quiz['description']); ?></p>
                <div class="flex items-center text-sm text-gray-500 mt-4">
                    <span class="text-accent">Created by: <?php echo htmlspecialchars($quiz['creator_name']); ?></span>
                    <span class="mx-2 text-accent">•</span>
                    <span class="text-accent"><?php echo count($questions); ?> questions</span>
                </div>
            </div>
            
            <div id="quiz-start-screen" class="p-6">
                <div class="text-center py-8">
                    <h2 class="text-xl font-semibold mb-4 text-accent">Ready to start the quiz?</h2>
                    <p class="text-gray-400 mb-6">
                        This quiz has <?php echo count($questions); ?> questions with a default time limit of <?php echo $quiz['default_time_limit']; ?> seconds per question.
                        <br>Some questions may have custom time limits.
                    </p>
                    <button id="start-quiz-btn" class="bg-button-primary text-accent py-2 px-6 rounded-md hover:bg-button-hover focus:outline-none focus:ring-2 focus:ring-secondary focus:ring-offset-2 transition-all duration-300">
                        Start Quiz
                    </button>
                    <a href="dashboard.php" class="block mt-4 text-link hover:underline">
                        Cancel and return to dashboard
                    </a>
                </div>
            </div>
            
            <div id="quiz-questions-screen" class="hidden">
                <form id="quiz-form" action="process_quiz.php" method="POST">
                    <input type="hidden" name="quiz_id" value="<?php echo $quiz_id; ?>">
                    
                    <div class="px-6 pt-6 pb-2">
                        <div class="flex items-center justify-between mb-2">
                            <div class="text-sm font-medium text-accent">
                                Question <span id="current-question-num">1</span> of <?php echo count($questions); ?>
                            </div>
                            <div class="flex items-center text-sm text-accent">
                                <i class="fas fa-clock mr-1"></i>
                                <span id="timer-display">00:00</span>
                            </div>
                        </div>
                        <div class="w-full bg-input-bg border border-input-border rounded-full h-2.5">
                            <div id="progress-bar" class="bg-button-primary h-2.5 rounded-full" style="width: 0%"></div>
                        </div>
                    </div>
                    
                    <?php foreach ($questions as $q_index => $question): ?>
                        <div class="question-slide px-6 py-4 <?php echo $q_index === 0 ? '' : 'hidden'; ?>" data-question-index="<?php echo $q_index; ?>" data-time-limit="<?php echo $question['time_limit']; ?>">
                            <h3 class="text-lg font-medium mb-4 text-accent"><?php echo htmlspecialchars($question['text']); ?></h3>
                            
                            <div class="space-y-3">
                                <?php foreach ($question['options'] as $o_index => $option): ?>
                                    <div class="option-item flex items-center p-3 border border-input-border rounded-md hover:bg-input-border/20 cursor-pointer">
                                        <input type="radio" id="q<?php echo $q_index; ?>_o<?php echo $o_index; ?>" 
                                            name="answers[<?php echo $question['id']; ?>]" 
                                            value="<?php echo $option['id']; ?>" 
                                            class="h-4 w-4 text-button-primary focus:ring-button-primary border-input-border">
                                        <label for="q<?php echo $q_index; ?>_o<?php echo $o_index; ?>" class="ml-3 block text-sm font-medium text-accent cursor-pointer">
                                            <?php echo htmlspecialchars($option['text']); ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <div class="px-6 py-4 border-t border-input-border bg-input-bg flex justify-between">
                        <button type="button" id="prev-btn" class="text-button-primary py-2 px-4 rounded-md border border-button-primary hover:bg-input-border/20 focus:outline-none focus:ring-2 focus:ring-button-primary focus:ring-offset-2 hidden transition-all duration-300">
                            Previous
                        </button>
                        <div class="flex-1"></div>
                        <button type="button" id="next-btn" class="bg-button-primary text-accent py-2 px-4 rounded-md hover:bg-button-hover focus:outline-none focus:ring-2 focus:ring-button-primary focus:ring-offset-2 transition-all duration-300">
                            Next
                        </button>
                        <button type="submit" id="finish-btn" class="bg-green-600 text-accent py-2 px-4 rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 hidden transition-all duration-300">
                            Finish Quiz
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const startScreen = document.getElementById('quiz-start-screen');
            const questionsScreen = document.getElementById('quiz-questions-screen');
            const startBtn = document.getElementById('start-quiz-btn');
            const prevBtn = document.getElementById('prev-btn');
            const nextBtn = document.getElementById('next-btn');
            const finishBtn = document.getElementById('finish-btn');
            const progressBar = document.getElementById('progress-bar');
            const currentQuestionNum = document.getElementById('current-question-num');
            const timerDisplay = document.getElementById('timer-display');
            const questionSlides = document.querySelectorAll('.question-slide');
            const totalQuestions = questionSlides.length;

            let currentQuestionIndex = 0;
            let timer = null;
            let timeLeft = 0;

            startBtn.addEventListener('click', function () {
                startScreen.classList.add('hidden');
                questionsScreen.classList.remove('hidden');

                startQuestionTimer(0);
            });

            document.querySelectorAll('.option-item').forEach(item => {
                item.addEventListener('click', function () {
                    const radio = this.querySelector('input[type="radio"]');
                    radio.checked = true;
                });
            });

            prevBtn.addEventListener('click', function () {
                if (currentQuestionIndex > 0) {
                    clearInterval(timer);

                    showQuestion(currentQuestionIndex - 1);
                }
            });

            nextBtn.addEventListener('click', function () {
                if (currentQuestionIndex < totalQuestions - 1) {
                    clearInterval(timer);

                    showQuestion(currentQuestionIndex + 1);
                }
            });

            function showQuestion(index) {
                questionSlides.forEach(slide => {
                    slide.classList.add('hidden');
                });

                questionSlides[index].classList.remove('hidden');

                currentQuestionIndex = index;

                const progress = ((index) / totalQuestions) * 100;
                progressBar.style.width = `${progress}%`;

                currentQuestionNum.textContent = index + 1;

                if (index === 0) {
                    prevBtn.classList.add('hidden');
                } else {
                    prevBtn.classList.remove('hidden');
                }

                if (index === totalQuestions - 1) {
                    nextBtn.classList.add('hidden');
                    finishBtn.classList.remove('hidden');
                } else {
                    nextBtn.classList.remove('hidden');
                    finishBtn.classList.add('hidden');
                }

                startQuestionTimer(index);
            }

            function startQuestionTimer(index) {
                const questionSlide = questionSlides[index];
                timeLeft = parseInt(questionSlide.dataset.timeLimit);

                updateTimerDisplay();

                timer = setInterval(function () {
                    timeLeft--;
                    updateTimerDisplay();

                    if (timeLeft <= 0) {
                        clearInterval(timer);

                        if (currentQuestionIndex < totalQuestions - 1) {
                            showQuestion(currentQuestionIndex + 1);
                        } else {
                            document.getElementById('quiz-form').submit();
                        }
                    }
                }, 1000);
            }

            function updateTimerDisplay() {
                const minutes = Math.floor(timeLeft / 60);
                const seconds = timeLeft % 60;
                timerDisplay.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;

                if (timeLeft <= 5) {
                    timerDisplay.classList.add('text-red-600');
                } else {
                    timerDisplay.classList.remove('text-red-600');
                }
            }
        });
    </script>
</body>
</html>