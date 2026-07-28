<?php
declare(strict_types=1);

namespace App\User\Application\Usecases;

use App\User\Domain\Repositories\UserRepositoryInterface;
use App\User\Domain\Repositories\PasswordResetRepositoryInterface;
use App\User\Domain\ValueObjects\Email;
use App\User\Domain\Entities\PasswordReset;
use App\User\Application\DTOs\ForgotPasswordRequest;
use App\Security\Infrastructure\Services\GoogleRecaptchaService;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class ForgotPasswordUseCase
{
    private UserRepositoryInterface $userRepository;
    private PasswordResetRepositoryInterface $passwordResetRepository;
    private GoogleRecaptchaService $recaptchaService;

    public function __construct(
        UserRepositoryInterface $userRepository,
        PasswordResetRepositoryInterface $passwordResetRepository,
        GoogleRecaptchaService $recaptchaService
    ) {
        $this->userRepository = $userRepository;
        $this->passwordResetRepository = $passwordResetRepository;
        $this->recaptchaService = $recaptchaService;
    }

    public function execute(ForgotPasswordRequest $request): array
    {
        // 1. Validate request
        $errors = $request->validate();
        if (!empty($errors)) {
            return ['success' => false, 'message' => 'Validation failed', 'errors' => $errors];
        }

        // 2. Verify reCAPTCHA
        if (!$this->recaptchaService->verify($request->captchaToken)) {
            return [
                'success' => false,
                'message' => 'reCAPTCHA verification failed. Please try again.',
                'errors' => ['captcha' => 'reCAPTCHA verification failed']
            ];
        }

        // 3. Find user by email
        $email = new Email($request->email);
        $user = $this->userRepository->findByEmail($email);

        if (!$user) {
            // Don't reveal if email exists or not (security)
            return [
                'success' => true,
                'message' => 'If your email is registered, you will receive a reset code.'
            ];
        }

        // 4. Generate 4-digit reset code
        $resetCode = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);

        // 5. Create reset record
        $passwordReset = new PasswordReset(
            null,
            $user->getId()->getValue(),
            $request->email,
            $resetCode
        );

        $this->passwordResetRepository->create($passwordReset);

        // 6. Send email with reset code
        $emailSent = $this->sendPasswordResetEmail(
            $request->email,
            $user->getName(),
            $resetCode
        );

        if (!$emailSent) {
            // Clean up if email fails
            $this->passwordResetRepository->deleteByUserId($user->getId()->getValue());
            return [
                'success' => false,
                'message' => 'Failed to send reset email. Please try again.'
            ];
        }

        return [
            'success' => true,
            'message' => 'Password reset code sent to your email.',
            'data' => [
                'email' => $request->email
            ]
        ];
    }

    /**
     * Send password reset email
     */
    private function sendPasswordResetEmail(string $email, string $name, string $code): bool
    {
        try {
            $mail = new PHPMailer(true);
            
            // Server settings
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'kokyaw3482@gmail.com';
            $mail->Password   = 'fdrbwlxauqtioumr';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ];
            
            // Recipients
            $mail->setFrom('kokyaw3482@gmail.com', 'FOODIE');
            $mail->addAddress($email, $name);
            $mail->addReplyTo('kokyaw3482@gmail.com', 'FOODIE Support');
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = '🔑 Password Reset Code - FOODIE';
            $mail->Body    = $this->getPasswordResetEmailTemplate($name, $code);
            $mail->AltBody = "Your password reset code is: $code\n\nThis code will expire in 10 minutes.\n\nIf you didn't request this, please ignore this email.";
            
            $mail->send();
            return true;
            
        } catch (Exception $e) {
            error_log('Password Reset Email Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get password reset email template
     */
    private function getPasswordResetEmailTemplate(string $name, string $code): string
    {
        $year = date('Y');
        return <<<HTML
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px; }
                .container { max-width: 500px; margin: 0 auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
                .logo { text-align: center; font-size: 28px; font-weight: 800; color: #10B981; }
                .code { font-size: 36px; font-weight: 700; letter-spacing: 8px; text-align: center; padding: 20px; background: #F8FAFC; border-radius: 8px; margin: 20px 0; border: 2px dashed #E2E8F0; }
                .footer { text-align: center; color: #94A3B8; font-size: 12px; margin-top: 20px; border-top: 1px solid #E2E8F0; padding-top: 20px; }
                .info { color: #475569; line-height: 1.6; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="logo">🍔 FOODIE</div>
                <h2 style="text-align: center; color: #0f172a;">Reset Your Password</h2>
                <p class="info">Hello <strong>$name</strong>,</p>
                <p class="info">We received a request to reset your password for your FOODIE account.</p>
                <p class="info">Use the 4-digit code below to reset your password:</p>
                <div class="code">$code</div>
                <p class="info">⏱️ This code will expire in <strong>10 minutes</strong>.</p>
                <p class="info">If you didn't request a password reset, please ignore this email.</p>
                <div class="footer">&copy; $year FOODIE. All rights reserved.</div>
            </div>
        </body>
        </html>
        HTML;
    }
}