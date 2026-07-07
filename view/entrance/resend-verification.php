<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/../../vendor/autoload.php';

use App\User\Application\Usecases\SendVerificationUseCase;
use App\User\Infrastructure\Repositories\EmailVerificationRepository;
use App\User\Infrastructure\Repositories\UserRepository;

// ============================================
// 1. API ENDPOINT - RESEND VERIFICATION
// ============================================

header('Content-Type: application/json');

$userId = (int) ($_POST['user_id'] ?? 0);
$email = trim($_POST['email'] ?? '');

if ($userId <= 0 || empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit();
}

try {
    $userRepo = new UserRepository();
    $verificationRepo = new EmailVerificationRepository();
    $sendVerification = new SendVerificationUseCase($userRepo, $verificationRepo);
    $result = $sendVerification->execute($userId);
    
    // Return the code for testing (remove in production)
    echo json_encode($result);
} catch (\Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}