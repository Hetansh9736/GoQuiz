<?php
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('index.php');
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = sanitize($_POST['title']);
    $description = sanitize($_POST['description']);
    $default_time_limit = (int)$_POST['default_time_limit'];
    $is_public = (int)$_POST['is_public'];
    
    if (empty($title)) {
        $_SESSION['error'] = "Quiz title is required.";
        redirect('dashboard.php?tab=create');
    }
    
    if ($default_time_limit < 5 || $default_time_limit > 120) {
        $default_time_limit = 15; 
    }
    
    $conn->begin_transaction();
    
    try {
        $sql = "INSERT INTO quizzes (title, description, user_id, is_public, default_time_limit) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssiii", $title, $description, $user_id, $is_public, $default_time_limit);
        $stmt->execute();
        
        $quiz_id = $conn->insert_id;
        
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
        
        $_SESSION['success'] = "Quiz created successfully!";
        redirect('dashboard.php');
        
    } catch (Exception $e) {
        $conn->rollback();
        
        $_SESSION['error'] = $e->getMessage();
        redirect('dashboard.php?tab=create');
    }
} else {
    redirect('dashboard.php');
}
?>
