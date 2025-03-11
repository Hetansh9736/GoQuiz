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

$sql = "SELECT * FROM quizzes WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $quiz_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = "Quiz not found or you don't have permission to edit it.";
    redirect('dashboard.php');
}

$quiz = $result->fetch_assoc();

$questions = [];
$sql = "SELECT * FROM questions WHERE quiz_id = ? ORDER BY question_order";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $quiz_id);
$stmt->execute();
$result = $stmt->get_result();

while ($question = $result->fetch_assoc()) {
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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = sanitize($_POST['title']);
    $description = sanitize($_POST['description']);
    $default_time_limit = (int)$_POST['default_time_limit'];
    $is_public = (int)$_POST['is_public'];

    if (empty($title)) {
        $_SESSION['error'] = "Quiz title is required.";
        redirect("edit_quiz.php?id=$quiz_id");
    }

    if ($default_time_limit < 5 || $default_time_limit > 120) {
        $default_time_limit = 15; 
    }

    $conn->begin_transaction();

    try {
        $sql = "UPDATE quizzes SET title = ?, description = ?, is_public = ?, default_time_limit = ? WHERE id = ? AND user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssiiii", $title, $description, $is_public, $default_time_limit, $quiz_id, $user_id);
        $stmt->execute();

        $sql = "DELETE FROM questions WHERE quiz_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $quiz_id);
        $stmt->execute();

        if (isset($_POST['questions']) && is_array($_POST['questions'])) {
            foreach ($_POST['questions'] as $q_index => $question) {
                if (empty($question['text'])) {
                    throw new Exception("Question text is required for question " . ($q_index + 1));
                }

                $time_limit = isset($question['time_limit']) ? (int)$question['time_limit'] : $default_time_limit;
                if ($time_limit < 5 || $time_limit > 120) {
                    $time_limit = $default_time_limit;
                }

                $sql = "INSERT INTO questions (quiz_id, text, time_limit, question_order) VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("isii", $quiz_id, $question['text'], $time_limit, $q_index);
                $stmt->execute();

                $question_id = $conn->insert_id;

                if (isset($question['options']) && is_array($question['options'])) {
                    $correct_option = isset($question['correct_option']) ? (int)$question['correct_option'] : -1;

                    foreach ($question['options'] as $o_index => $option) {
                        if (empty($option['text'])) {
                            throw new Exception("Option text is required for question " . ($q_index + 1) . ", option " . ($o_index + 1));
                        }

                        $is_correct = ($o_index == $correct_option) ? 1 : 0;

                        $sql = "INSERT INTO options (question_id, text, is_correct, option_order) VALUES (?, ?, ?, ?)";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("isii", $question_id, $option['text'], $is_correct, $o_index);
                        $stmt->execute();
                    }
                } else {
                    throw new Exception("Options are required for question " . ($q_index + 1));
                }
            }
        } else {
            throw new Exception("At least one question is required.");
        }

        $conn->commit();

        $_SESSION['success'] = "Quiz updated successfully!";
        redirect('dashboard.php');
    } catch (Exception $e) {
        $conn->rollback();

        $_SESSION['error'] = $e->getMessage();
        redirect("edit_quiz.php?id=$quiz_id");
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Quiz - Quiz App</title>
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

<body class="bg-primary min-h-screen font-poppins">
    <nav class="bg-input-bg bg-opacity-75 backdrop-blur-lg shadow-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <div class="flex-shrink-0 flex items-center">
                        <h1 class="text-xl font-bold text-accent">Quiz App</h1>
                    </div>
                </div>
                <div class="flex items-center">
                    <a href="dashboard.php" class="text-sm text-link hover:text-secondary mr-4">
                        <i class="fas fa-arrow-left mr-1"></i> Back to Dashboard
                    </a>
                    <a href="logout.php" class="text-sm text-red-600 hover:text-red-800">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <h2 class="text-2xl font-semibold mb-6 text-accent">Edit Quiz</h2>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-error-bg border border-error-border text-error-text px-4 py-3 rounded relative mb-4" role="alert">
                <?php echo $_SESSION['error']; ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <div class="bg-input-bg bg-opacity-75 backdrop-blur-lg rounded-3xl shadow-lg overflow-hidden animate-fadeIn">
            <div class="p-6">
                <form action="edit_quiz.php?id=<?php echo $quiz_id; ?>" method="POST" id="edit-quiz-form">
                    <div class="space-y-6">
                        <div class="space-y-4">
                            <div>
                                <label for="title" class="block text-sm font-medium text-accent mb-1">Quiz Title</label>
                                <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($quiz['title']); ?>" required
                                    class="w-full px-3 py-2 bg-input-bg border border-input-border rounded-xl text-accent placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary transition-all duration-300">
                            </div>

                            <div>
                                <label for="description" class="block text-sm font-medium text-accent mb-1">Description</label>
                                <textarea id="description" name="description" rows="3"
                                    class="w-full px-3 py-2 bg-input-bg border border-input-border rounded-xl text-accent placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary transition-all duration-300"><?php echo htmlspecialchars($quiz['description']); ?></textarea>
                            </div>

                            <div class="flex items-center space-x-4">
                                <div class="flex-1">
                                    <label for="default_time_limit" class="block text-sm font-medium text-accent mb-1">Default Time Limit (seconds)</label>
                                    <input type="number" id="default_time_limit" name="default_time_limit" min="5" max="120" value="<?php echo $quiz['default_time_limit']; ?>" required
                                        class="w-full px-3 py-2 bg-input-bg border border-input-border rounded-xl text-accent placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary transition-all duration-300">
                                </div>

                                <div class="flex-1">
                                    <label for="visibility" class="block text-sm font-medium text-accent mb-1">Visibility</label>
                                    <select id="visibility" name="is_public"
                                        class="w-full px-3 py-2 bg-input-bg border border-input-border rounded-xl text-accent focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary transition-all duration-300">
                                        <option value="1" <?php echo $quiz['is_public'] ? 'selected' : ''; ?>>Public</option>
                                        <option value="0" <?php echo !$quiz['is_public'] ? 'selected' : ''; ?>>Private</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-medium text-accent">Questions</h3>
                                <button type="button" id="add-question-btn"
                                    class="inline-flex items-center px-3 py-1 border border-transparent text-sm leading-5 font-medium rounded-md text-secondary bg-blue-100 hover:bg-blue-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-secondary">
                                    <i class="fas fa-plus mr-1"></i> Add Question
                                </button>
                            </div>

                            <div id="questions-container">
                                <?php foreach ($questions as $q_index => $question): ?>
                                    <div class="question-item bg-input-bg p-4 rounded-md mb-4 border border-input-border">
                                        <div class="flex justify-between items-start mb-3">
                                            <h4 class="font-medium text-accent">Question <?php echo $q_index + 1; ?></h4>
                                            <button type="button" class="remove-question-btn text-red-600 hover:text-red-800" <?php echo count($questions) <= 1 ? 'disabled' : ''; ?>>
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                        <div class="space-y-4">
                                            <div>
                                                <label class="block text-sm font-medium text-accent mb-1">Question Text</label>
                                                <input type="text" name="questions[<?php echo $q_index; ?>][text]" value="<?php echo htmlspecialchars($question['text']); ?>" required
                                                    class="w-full px-3 py-2 bg-input-bg border border-input-border rounded-xl text-accent placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary transition-all duration-300">
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium text-accent mb-1">Time Limit (seconds)</label>
                                                <input type="number" name="questions[<?php echo $q_index; ?>][time_limit]" min="5" max="120" value="<?php echo $question['time_limit']; ?>" required
                                                    class="w-full px-3 py-2 bg-input-bg border border-input-border rounded-xl text-accent placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary transition-all duration-300">
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium text-accent mb-2">Options (select the correct answer)</label>

                                                <div class="space-y-2">
                                                    <?php foreach ($question['options'] as $o_index => $option): ?>
                                                        <div class="flex items-center gap-2">
                                                            <div class="flex-none w-6 h-6 flex items-center justify-center rounded-full border text-xs font-medium text-accent border-input-border bg-input-bg">
                                                                <?php echo chr(65 + $o_index); ?>
                                                            </div>
                                                            <input type="text" name="questions[<?php echo $q_index; ?>][options][<?php echo $o_index; ?>][text]"
                                                                value="<?php echo htmlspecialchars($option['text']); ?>" required
                                                                class="flex-1 px-3 py-2 bg-input-bg border border-input-border rounded-xl text-accent placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary transition-all duration-300">
                                                            <div class="flex items-center">
                                                                <input type="radio" name="questions[<?php echo $q_index; ?>][correct_option]" value="<?php echo $o_index; ?>"
                                                                    <?php echo $option['is_correct'] ? 'checked' : ''; ?> required
                                                                    class="h-4 w-4 text-secondary focus:ring-secondary border-input-border">
                                                                <label class="ml-2 text-sm text-accent">Correct</label>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="pt-4 border-t border-input-border">
                            <button type="submit" class="w-full text-accent py-2 px-4 rounded-md bg-button-primary hover:bg-button-hover focus:outline-none focus:ring-2 focus:ring-secondary focus:ring-offset-2 focus:ring-offset-primary transition-all duration-300">
                                <i class="fas fa-save mr-1"></i> Update Quiz
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const addQuestionBtn = document.getElementById('add-question-btn');
            const questionsContainer = document.getElementById('questions-container');

            if (addQuestionBtn && questionsContainer) {
                let questionCount = <?php echo count($questions); ?>;

                addQuestionBtn.addEventListener('click', function() {
                    questionCount++;

                    const questionTemplate = `
                        <div class="question-item bg-input-bg p-4 rounded-md mb-4 border border-input-border">
                            <div class="flex justify-between items-start mb-3">
                                <h4 class="font-medium text-accent">Question <span class="math-inline">\{questionCount\}</h4\>
<button type\="button" class\="remove\-question\-btn text\-red\-600 hover\:text\-red\-800"\>
<i class\="fas fa\-trash"\></i\>
</button\>
</div\>
<div class\="space\-y\-4"\>
<div\>
<label class\="block text\-sm font\-medium text\-accent mb\-1"\>Question Text</label\>
<input type\="text" name\="questions\[</span>{questionCount - 1}][text]" required 
                                        class="w-full px-3 py-2 bg-input-bg border border-input-border rounded-xl text-accent placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary transition-all duration-300">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-accent mb-1">Time Limit (seconds)</label>
                                    <input type="number" name="questions[${questionCount - 1}][time_limit]" min="5" max="120" value="15" required 
                                        class="w-full px-3 py-2 bg-input-bg border border-input-border rounded-xl text-accent placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary transition-all duration-300">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-accent mb-2">Options (select the correct answer)</label>
                                    <div class="space-y-2">
                                        ${['A', 'B', 'C', 'D'].map((letter, index) => `
                                            <div class="flex items-center gap-2">
                                                <div class="flex-none w-6 h-6 flex items-center justify-center rounded-full border text-xs font-medium text-accent border-input-border bg-input-bg">${letter}</div>
                                                <input type="text" name="questions[${questionCount - 1}][options][${index}][text]" placeholder="Option ${index + 1}" required 
                                                    class="flex-1 px-3 py-2 bg-input-bg border border-input-border rounded-xl text-accent placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary transition-all duration-300">
                                                <div class="flex items-center">
                                                    <input type="radio" name="questions[${questionCount - 1}][correct_option]" value="${index}" required 
                                                        class="h-4 w-4 text-secondary focus:ring-secondary border-input-border">
                                                    <label class="ml-2 text-sm text-accent">Correct</label>
                                                </div>
                                            </div>
                                        `).join('')}
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;

                    questionsContainer.insertAdjacentHTML('beforeend', questionTemplate);

                    const removeButtons = document.querySelectorAll('.remove-question-btn');
                    removeButtons.forEach(button => {
                        button.disabled = false;
                    });
                });

                questionsContainer.addEventListener('click', function(e) {
                    if (e.target.closest('.remove-question-btn')) {
                        const questionItem = e.target.closest('.question-item');

                        if (document.querySelectorAll('.question-item').length > 1) {
                            questionItem.remove();

                            document.querySelectorAll('.question-item').forEach((item, index) => {
                                item.querySelector('h4').textContent = `Question ${index + 1}`;

                                const inputs = item.querySelectorAll('input[name^="questions["]');
                                inputs.forEach(input => {
                                    const oldName = input.name;
                                    const newName = oldName.replace(/questions\[\d+\]/, `questions[${index}]`);
                                    input.name = newName;
                                });

                                const radios = item.querySelectorAll('input[name^="questions["][type="radio"]');
                                radios.forEach(radio => {
                                    const oldRadioName = radio.name;
                                    const newRadioName = oldRadioName.replace(/questions\[\d+\]/, `questions[${index}]`);
                                    radio.name = newRadioName;
                                });
                            });

                            if (document.querySelectorAll('.question-item').length === 1) {
                                document.querySelector('.remove-question-btn').disabled = true;
                            }
                        }
                    }
                });
            }
        });
    </script>
</body>

</html>