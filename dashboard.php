<?php
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('index.php');
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

$sql = "SELECT q.*, u.name as creator_name, 
        (SELECT COUNT(*) FROM questions WHERE quiz_id = q.id) as question_count 
        FROM quizzes q 
        JOIN users u ON q.user_id = u.id 
        WHERE q.is_public = 1 OR q.user_id = ? 
        ORDER BY q.created_at DESC";

$quizzes = [];

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $user_id);

    if ($stmt->execute()) {
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $quizzes[] = $row;
        }
    }

    $stmt->close();
}

$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'list';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Quiz App</title>
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
                        'input-bg': '#3B415A',
                        'accent': '#F2F2F2',
                        'input-border': '#5A6782',
                        'secondary': '#1B9AAA',
                        'button-primary': '#1B9AAA',
                        'button-hover': '#168292',
                        'error-bg': '#F8D7DA',
                        'error-text': '#842029',
                        'error-border': '#F5C6CB',
                        'success-bg': '#D1F2EB',
                        'success-text': '#155724',
                        'success-border': '#C3E6CB',
                        'link': '#48D1CC',
                        'quiz-card': '#414860',
                        'quiz-card-footer': '#363B4F',
                        'public-tag-text': '#065f46',
                        'public-tag-bg': '#D1FAE5',
                        'private-tag-bg': '#F3E8FF',
                        'private-tag-text': '#6b21a8'
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
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>

<body class="bg-primary min-h-screen font-poppins">
    <nav class="bg-input-bg shadow-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
            <div class="flex justify-center">
        <h1 class="text-xl font-bold text-secondary flex items-center animate-pulse">
            Go&nbsp;Quiz
        </h1>
    </div>
                <div class="flex items-center">
                    <span class="text-sm text-gray-300 mr-4">Welcome, <?php echo htmlspecialchars($user_name); ?></span>
                    <a href="logout.php" class="text-sm text-red-400 hover:text-red-600">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="mb-6 border-b border-input-border">
            <div class="flex -mb-px">
                <a href="?tab=list" class="<?php echo $active_tab == 'list' ? 'border-secondary text-secondary' : 'border-transparent text-gray-300 hover:text-gray-100 hover:border-gray-100'; ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm mr-8">
                    <i class="fas fa-list mr-2"></i> Quiz List
                </a>
                <a href="?tab=create" class="<?php echo $active_tab == 'create' ? 'border-secondary text-secondary' : 'border-transparent text-gray-300 hover:text-gray-100 hover:border-gray-100'; ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    <i class="fas fa-plus-circle mr-2"></i> Create Quiz
                </a>
            </div>
        </div>

        <?php if ($active_tab == 'list'): ?>
            <div>
                <h2 class="text-xl font-semibold text-accent mb-4">Available Quizzes</h2>

                <?php if (empty($quizzes)): ?>
                    <div class="bg-input-bg rounded-lg border border-input-border p-6 text-center">
                        <p class="text-gray-300">No quizzes available. Create your first quiz!</p>
                        <a href="?tab=create" class="mt-4 inline-block bg-button-primary text-accent py-2 px-4 rounded-md hover:bg-button-hover">Create Quiz</a>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php foreach ($quizzes as $quiz): ?>
                            <div class="bg-quiz-card rounded-lg shadow-md overflow-hidden">
                                <div class="p-6">
                                    <div class="flex justify-between items-start">
                                        <h3 class="text-lg font-semibold text-accent"><?php echo htmlspecialchars($quiz['title']); ?></h3>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $quiz['is_public'] ? 'bg-public-tag-bg text-public-tag-text' : 'bg-private-tag-bg text-private-tag-text'; ?>">
                                            <?php echo $quiz['is_public'] ? 'Public' : 'Private'; ?>
                                        </span>
                                    </div>
                                    <p class="mt-2 text-sm text-gray-300 line-clamp-2"><?php echo htmlspecialchars($quiz['description']); ?></p>
                                    <div class="mt-4 flex items-center text-sm text-gray-400">
                                        <i class="fas fa-user mr-1"></i>
                                        <span><?php echo htmlspecialchars($quiz['creator_name']); ?></span>
                                        <span class="mx-2">•</span>
                                        <i class="fas fa-question-circle mr-1"></i>
                                        <span><?php echo $quiz['question_count']; ?> questions</span>
                                        <span class="mx-2">•</span>
                                        <i class="fas fa-clock mr-1"></i>
                                        <span><?php echo $quiz['default_time_limit']; ?>s</span>
                                    </div>
                                </div>
                                <div class="bg-quiz-card-footer px-6 py-3 flex justify-between">
                                    <?php if ($quiz['user_id'] == $user_id): ?>
                                        <a href="edit_quiz.php?id=<?php echo $quiz['id']; ?>" class="text-secondary hover:text-teal-300 text-sm">
                                            <i class="fas fa-edit mr-1"></i> Edit
                                        </a>
                                    <?php else: ?>
                                        <span></span>
                                    <?php endif; ?>
                                    <a href="take_quiz.php?id=<?php echo $quiz['id']; ?>" class="bg-button-primary text-accent py-1 px-3 rounded-md text-sm hover:bg-button-hover">
                                        Take Quiz
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div>
                <h2 class="text-xl font-semibold text-accent mb-4">Create New Quiz</h2>
                <div class="bg-input-bg rounded-lg shadow-md overflow-hidden">
                    <div class="p-6">
                        <form action="save_quiz.php" method="POST" id="create-quiz-form">
                            <div class="space-y-6">
                                <div class="space-y-4">
                                    <div>
                                        <label for="title" class="block text-sm font-medium text-accent mb-1">Quiz Title</label>
                                        <input type="text" id="title" name="title" required
                                            class="w-full px-3 py-2 bg-input-bg border border-input-border rounded-md text-accent placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary transition-all duration-300">
                                    </div>

                                    <div>
                                        <label for="description" class="block text-sm font-medium text-accent mb-1">Description</label>
                                        <textarea id="description" name="description" rows="3"
                                            class="w-full px-3 py-2 bg-input-bg border border-input-border rounded-md text-accent placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary transition-all duration-300"></textarea>
                                    </div>

                                    <div class="flex items-center space-x-4">
                                        <div class="flex-1">
                                            <label for="default_time_limit" class="block text-sm font-medium text-accent mb-1">Default Time Limit (seconds)</label>
                                            <input type="number" id="default_time_limit" name="default_time_limit" min="5" max="120" value="15" required
                                                class="w-full px-3 py-2 bg-input-bg border border-input-border rounded-md text-accent placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary transition-all duration-300">
                                        </div>

                                        <div class="flex-1">
                                            <label for="visibility" class="block text-sm font-medium text-accent mb-1">Visibility</label>
                                            <select id="visibility" name="is_public"
                                                class="w-full px-3 py-2 bg-input-bg border border-input-border rounded-md text-accent focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary transition-all duration-300">
                                                <option value="1">Public</option>
                                                <option value="0">Private</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <div class="flex items-center justify-between mb-4">
                                        <h3 class="text-lg font-medium text-accent">Questions</h3>
                                        <button type="button" id="add-question-btn"
                                            class="inline-flex items-center px-3 py-1 border border-transparent text-sm leading-5 font-medium rounded-md text-button-primary bg-input-bg hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-secondary">
                                            <i class="fas fa-plus mr-1"></i> Add Question
                                        </button>
                                    </div>

                                    <div id="questions-container">
                                        <div class="question-item bg-quiz-card p-4 rounded-md mb-4">
                                            <div class="flex justify-between items-start mb-3">
                                                <h4 class="font-medium text-accent">Question 1</h4>
                                                <button type="button" class="remove-question-btn text-red-400 hover:text-red-600" disabled>
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>

                                            <div class="space-y-4">
                                                <div>
                                                    <label class="block text-sm font-medium text-accent mb-1">Question Text</label>
                                                    <input type="text" name="questions[0][text]" required
                                                        class="w-full px-3 py-2 bg-input-bg border border-input-border rounded-md text-accent placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary transition-all duration-300">
                                                </div>

                                                <div>
                                                    <label class="block text-sm font-medium text-accent mb-1">Time Limit (seconds)</label>
                                                    <input type="number" name="questions[0][time_limit]" min="5" max="120" value="15" required
                                                        class="w-full px-3 py-2 bg-input-bg border border-input-border rounded-md text-accent placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary transition-all duration-300">
                                                </div>
                                                <div>
                                                    <label class="block text-sm font-medium text-accent mb-2">Options (select the correct answer)</label>

                                                    <div class="space-y-2">
                                                        <div class="flex items-center gap-2">
                                                            <div class="flex-none w-6 h-6 flex items-center justify-center rounded-full border border-accent text-xs font-medium text-accent">A</div>
                                                            <input type="text" name="questions[0][options][0][text]" placeholder="Option 1" required
                                                                class="w-full px-3 py-2 bg-input-bg border border-input-border rounded-md text-accent placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary transition-all duration-300">
                                                            <div class="flex items-center">
                                                                <input type="radio" name="questions[0][correct_option]" value="0" required
                                                                    class="h-4 w-4 text-secondary focus:ring-secondary border-gray-300">
                                                                <label class="ml-2 text-sm text-accent">Correct</label>
                                                            </div>
                                                        </div>
                                                        <div class="flex items-center gap-2">
                                                            <div class="flex-none w-6 h-6 flex items-center justify-center rounded-full border border-accent text-xs font-medium text-accent">B</div>
                                                            <input type="text" name="questions[0][options][1][text]" placeholder="Option 2" required
                                                                class="w-full px-3 py-2 bg-input-bg border border-input-border rounded-md text-accent placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary transition-all duration-300">
                                                            <div class="flex items-center">
                                                                <input type="radio" name="questions[0][correct_option]" value="1"
                                                                    class="h-4 w-4 text-secondary focus:ring-secondary border-gray-300">
                                                                <label class="ml-2 text-sm text-accent">Correct</label>
                                                            </div>
                                                        </div>
                                                        <div class="flex items-center gap-2">
                                                            <div class="flex-none w-6 h-6 flex items-center justify-center rounded-full border border-accent text-xs font-medium text-accent">C</div>
                                                            <input type="text" name="questions[0][options][2][text]" placeholder="Option 3" required
                                                                class="w-full px-3 py-2 bg-input-bg border border-input-border rounded-md text-accent placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-secondary focus class=" w-full px-3 py-2 bg-input-bg border border-input-border rounded-md text-accent placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary transition-all duration-300">
                                                            <div class="flex items-center">
                                                                <input type="radio" name="questions[0][correct_option]" value="2"
                                                                    class="h-4 w-4 text-secondary focus:ring-secondary border-gray-300">
                                                                <label class="ml-2 text-sm text-accent">Correct</label>
                                                            </div>
                                                        </div>
                                                        <div class="flex items-center gap-2">
                                                            <div class="flex-none w-6 h-6 flex items-center justify-center rounded-full border border-accent text-xs font-medium text-accent">D</div>
                                                            <input type="text" name="questions[0][options][3][text]" placeholder="Option 4" required
                                                                class="w-full px-3 py-2 bg-input-bg border border-input-border rounded-md text-accent placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary transition-all duration-300">
                                                            <div class="flex items-center">
                                                                <input type="radio" name="questions[0][correct_option]" value="3"
                                                                    class="h-4 w-4 text-secondary focus:ring-secondary border-gray-300">
                                                                <label class="ml-2 text-sm text-accent">Correct</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="pt-4 border-t border-input-border">
                                        <button type="submit" class="w-full bg-button-primary text-accent py-2 px-4 rounded-md hover:bg-button-hover focus:outline-none focus:ring-2 focus:ring-secondary focus:ring-offset-2">
                                            <i class="fas fa-save mr-1"></i> Save Quiz
                                        </button>
                                    </div>
                                </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const addQuestionBtn = document.getElementById('add-question-btn');
            const questionsContainer = document.getElementById('questions-container');

            if (addQuestionBtn && questionsContainer) {
                let questionCount = 1;

                addQuestionBtn.addEventListener('click', function() {
                    questionCount++;

                    const questionTemplate = `
                            <div class="question-item bg-quiz-card p-4 rounded-md mb-4">
                                <div class="flex justify-between items-start mb-3">
                                    <h4 class="font-medium text-accent">Question ${questionCount}</h4>
                                    <button type="button" class="remove-question-btn text-red-400 hover:text-red-600">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>

                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-accent mb-1">Question Text</label>
                                        <input type="text" name="questions[${questionCount-1}][text]" required
                                               class="w-full px-3 py-2 bg-input-bg border border-input-border rounded-md text-accent placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary transition-all duration-300">
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-accent mb-1">Time Limit (seconds)</label>
                                        <input type="number" name="questions[${questionCount-1}][time_limit]" min="5" max="120" value="15" required
                                               class="w-full px-3 py-2 bg-input-bg border border-input-border rounded-md text-accent placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary transition-all duration-300">
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-accent mb-2">Options (select the correct answer)</label>

                                        <div class="space-y-2">
                                            <div class="flex items-center gap-2">
                                                <div class="flex-none w-6 h-6 flex items-center justify-center rounded-full border border-accent text-xs font-medium text-accent">A</div>
                                                <input type="text" name="questions[${questionCount-1}][options][0][text]" placeholder="Option 1" required
                                                       class="w-full px-3 py-2 bg-input-bg border border-input-border rounded-md text-accent placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary transition-all duration-300">
                                                <div class="flex items-center">
                                                    <input type="radio" name="questions[${questionCount-1}][correct_option]" value="0" required
                                                           class="h-4 w-4 text-secondary focus:ring-secondary border-gray-300">
                                                    <label class="ml-2 text-sm text-accent">Correct</label>
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <div class="flex-none w-6 h-6 flex items-center justify-center rounded-full border border-accent text-xs font-medium text-accent">B</div>
                                                <input type="text" name="questions[${questionCount-1}][options][1][text]" placeholder="Option 2" required
                                                       class="w-full px-3 py-2 bg-input-bg border border-input-border rounded-md text-accent placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary transition-all duration-300">
                                                <div class="flex items-center">
                                                    <input type="radio" name="questions[${questionCount-1}][correct_option]" value="1"
                                                           class="h-4 w-4 text-secondary focus:ring-secondary border-gray-300">
                                                    <label class="ml-2 text-sm text-accent">Correct</label>
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <div class="flex-none w-6 h-6 flex items-center justify-center rounded-full border border-accent text-xs font-medium text-accent">C</div>
                                                <input type="text" name="questions[${questionCount-1}][options][2][text]" placeholder="Option 3" required
                                                       class="w-full px-3 py-2 bg-input-bg border border-input-border rounded-md text-accent placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary transition-all duration-300">
                                                <div class="flex items-center">
                                                    <input type="radio" name="questions[${questionCount-1}][correct_option]" value="2"
                                                           class="h-4 w-4 text-secondary focus:ring-secondary border-gray-300">
                                                    <label class="ml-2 text-sm text-accent">Correct</label>
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <div class="flex-none w-6 h-6 flex items-center justify-center rounded-full border border-accent text-xs font-medium text-accent">D</div>
                                                <input type="text" name="questions[${questionCount-1}][options][3][text]" placeholder="Option 4" required
                                                       class="w-full px-3 py-2 bg-input-bg border border-input-border rounded-md text-accent placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary transition-all duration-300">
                                                <div class="flex items-center">
                                                    <input type="radio" name="questions[${questionCount-1}][correct_option]" value="3"
                                                           class="h-4 w-4 text-secondary focus:ring-secondary border-gray-300">
                                                    <label class="ml-2 text-sm text-accent">Correct</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;

                    questionsContainer.insertAdjacentHTML('beforeend', questionTemplate);

                    // Enable all remove buttons when we have more than one question
                    const removeButtons = document.querySelectorAll('.remove-question-btn');
                    removeButtons.forEach(button => {
                        button.disabled = false;
                    });
                });

                // Event delegation for remove question buttons
                questionsContainer.addEventListener('click', function(e) {
                    if (e.target.closest('.remove-question-btn')) {
                        const questionItem = e.target.closest('.question-item');

                        // Only remove if we have more than one question
                        if (document.querySelectorAll('.question-item').length > 1) {
                            questionItem.remove();

                            // Renumber the questions
                            document.querySelectorAll('.question-item').forEach((item, index) => {
                                item.querySelector('h4').textContent = `Question ${index + 1}`;

                                // Update the name attributes for all inputs in this question
                                const inputs = item.querySelectorAll('input[name^="questions["]');
                                inputs.forEach(input => {
                                    const name = input.getAttribute('name');
                                    const newName = name.replace(/questions\[\d+\]/, `questions[${index}]`);
                                    input.setAttribute('name', newName);
                                });
                            });

                            // If only one question remains, disable its remove button
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