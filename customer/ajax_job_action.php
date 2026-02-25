<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['id']) && !isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login to continue.']);
    exit;
}

$user_id = $_SESSION['id'] ?? $_SESSION['user_id'];

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$action = $_POST['action'] ?? '';

if ($action === 'update_app_status') {
    $app_id = intval($_POST['app_id'] ?? 0);
    $new_status = $_POST['new_status'] ?? $_POST['status'] ?? '';
    $notes = sanitize($_POST['notes'] ?? '');
    $interview_date = !empty($_POST['interview_date']) ? $_POST['interview_date'] : null;

    if (!$app_id || !in_array($new_status, ['pending', 'shortlisted', 'interviewed', 'hired', 'rejected'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid parameters provided.']);
        exit;
    }

    try {
        // Verify that the application belongs to a job posted by the current user
        $stmt = $pdo->prepare("SELECT ja.id, ja.applicant_id, jl.title FROM job_applications ja
                               JOIN job_listings jl ON ja.job_id = jl.id
                               WHERE ja.id = ? AND jl.posted_by = ?");
        $stmt->execute([$app_id, $user_id]);
        $app = $stmt->fetch();

        if (!$app) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized action or application not found.']);
            exit;
        }

        // Update the status
        $updateStmt = $pdo->prepare("UPDATE job_applications SET status = ?, notes = ?, interview_date = ?, updated_at = NOW() WHERE id = ?");
        $updateStmt->execute([$new_status, $notes, $interview_date, $app_id]);

        // Trigger email notification
        sendJobApplicationEmail($pdo, $app_id);

        // Define notification messages
        $notif_msgs = [
            'shortlisted' => "🎉 You've been shortlisted for the position '{$app['title']}'!",
            'interviewed' => "📅 An interview has been scheduled for '{$app['title']}'" . ($interview_date ? " on " . date('M d, Y H:i', strtotime($interview_date)) : '') . ".",
            'hired' => "🥳 Fantastic news! You've been hired for the position '{$app['title']}'!",
            'rejected' => "We regret to inform you that your application for '{$app['title']}' was not selected at this time.",
            'pending' => "The status of your application for '{$app['title']}' has been updated to pending.",
        ];

        // Create notification for the applicant
        try {
            $pdo->prepare("INSERT INTO job_notifications (user_id, title, message, link) VALUES (?,?,?,?)")
                ->execute([
                    $app['applicant_id'],
                    ucfirst($new_status) . ' Update: ' . $app['title'],
                    $notif_msgs[$new_status],
                    'jobs.php?tab=my_apps'
                ]);
        } catch (Exception $e) {
            // Log notification error but don't fail the AJAX response
        }

        echo json_encode([
            'success' => true,
            'message' => 'Application status updated successfully.',
            'new_status' => $new_status,
            'status_label' => ucfirst($new_status)
        ]);

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

if ($action === 'toggle_job_status') {
    $job_id = intval($_POST['job_id'] ?? 0);
    if (!$job_id) {
        echo json_encode(['success' => false, 'message' => 'Invalid Job ID.']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT status FROM job_listings WHERE id = ? AND posted_by = ?");
        $stmt->execute([$job_id, $user_id]);
        $job = $stmt->fetch();

        if (!$job) {
            echo json_encode(['success' => false, 'message' => 'Job not found or unauthorized.']);
            exit;
        }

        $new_status = ($job['status'] === 'active') ? 'closed' : 'active';
        $pdo->prepare("UPDATE job_listings SET status = ? WHERE id = ?")->execute([$new_status, $job_id]);

        echo json_encode([
            'success' => true,
            'message' => "Job status changed to " . ucfirst($new_status),
            'new_status' => $new_status
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => "Invalid action: $action"]);
