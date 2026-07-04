<?php
namespace App\User\Application\Usecases;

use App\User\Domain\Repositories\UserRepositoryInterface;
use App\User\Infrastructure\Repositories\EmailVerificationRepository;
use App\User\Domain\ValueObjects\UserId;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class SendVerificationUseCase
{
    private UserRepositoryInterface $userRepository;
    private EmailVerificationRepository $verificationRepository;

    public function __construct(
        UserRepositoryInterface $userRepository,
        EmailVerificationRepository $verificationRepository
    ) {
        $this->userRepository = $userRepository;
        $this->verificationRepository = $verificationRepository;
    }

    public function execute(int $userId): array
    {
        error_log('=== SendVerificationUseCase START ===');
        error_log('User ID: ' . $userId);
        
        $user = $this->userRepository->findById(new UserId($userId));
        
        if (!$user) {
            error_log('❌ User not found for ID: ' . $userId);
            return ['success' => false, 'message' => 'User not found.'];
        }

        error_log('✅ User found: ' . $user->getEmail()->getValue());

        if ($user->isVerified()) {
            error_log('⚠️ Email already verified');
            return ['success' => false, 'message' => 'Email already verified.'];
        }

        // Generate 4-digit code
        $code = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        error_log('🔑 Generated code: ' . $code);
        
        // Save verification
        $saved = $this->verificationRepository->saveVerification(
            $userId,
            $user->getEmail()->getValue(),
            $code
        );

        if (!$saved) {
            error_log('❌ Failed to save verification record');
            return ['success' => false, 'message' => 'Failed to save verification code.'];
        }

        error_log('✅ Verification record saved');

        // Send email with verification code
        $emailSent = $this->sendVerificationEmail(
            $user->getEmail()->getValue(),
            $user->getName(),
            $code
        );

        if (!$emailSent) {
            error_log('❌ Failed to send email');
            // Delete the verification record if email fails
            $this->verificationRepository->deleteVerification($userId);
            return ['success' => false, 'message' => 'Failed to send verification email. Please try again.'];
        }

        error_log('✅ Email sent successfully to: ' . $user->getEmail()->getValue());
        error_log('=== SendVerificationUseCase END ===');

        return [
            'success' => true,
            'message' => 'Verification code sent to your email.',
            'code' => $code // For debugging only - remove in production
        ];
    }

    private function sendVerificationEmail(string $email, string $name, string $code): bool
    {
        error_log('📧 Attempting to send email to: ' . $email);
        
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
            $mail->Subject = '🔐 Verify Your Foodie Account';
            $mail->Body    = $this->getEmailTemplate($name, $code);
            $mail->AltBody = "Your verification code is: $code\n\nThis code will expire in 10 minutes.";
            
            $mail->send();
            error_log('✅ Email sent successfully to: ' . $email);
            return true;
            
        } catch (Exception $e) {
            error_log('❌ PHPMailer Error: ' . $e->getMessage());
            error_log('❌ PHPMailer Error Info: ' . $mail->ErrorInfo ?? 'No error info');
            return false;
        }
    }

    private function getEmailTemplate(string $name, string $code): string
    {
        $year = date('Y');
        return <<<HTML
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Verify Your Email</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    background-color: #f4f4f4;
                    padding: 20px;
                    margin: 0;
                }
                .container {
                    max-width: 500px;
                    margin: 0 auto;
                    background: white;
                    padding: 30px;
                    border-radius: 12px;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
                }
                .logo {
                    text-align: center;
                    font-size: 28px;
                    font-weight: 800;
                    color: #10B981;
                    margin-bottom: 20px;
                }
                .logo span {
                    background: #D1FAE5;
                    padding: 4px 12px;
                    border-radius: 8px;
                }
                .code {
                    font-size: 36px;
                    font-weight: 700;
                    letter-spacing: 8px;
                    color: #1E293B;
                    text-align: center;
                    padding: 20px;
                    background: #F8FAFC;
                    border-radius: 8px;
                    margin: 20px 0;
                    border: 2px dashed #E2E8F0;
                }
                .footer {
                    text-align: center;
                    color: #94A3B8;
                    font-size: 12px;
                    margin-top: 20px;
                    border-top: 1px solid #E2E8F0;
                    padding-top: 20px;
                }
                .btn {
                    display: inline-block;
                    background: #10B981;
                    color: white;
                    padding: 10px 24px;
                    border-radius: 8px;
                    text-decoration: none;
                    font-weight: 600;
                }
                .btn:hover {
                    background: #059669;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="logo">🍔 <span>FOODIE</span></div>
                <h2 style="text-align: center; color: #1E293B; margin-top: 0;">Verify Your Email</h2>
                <p style="color: #475569; text-align: center; font-size: 16px;">
                    Hello <strong style="color: #1E293B;">$name</strong>,
                </p>
                <p style="color: #475569; text-align: center; font-size: 15px;">
                    Thank you for registering with FOODIE! Use the verification code below to complete your registration:
                </p>
                <div class="code">$code</div>
                <p style="color: #94A3B8; text-align: center; font-size: 13px;">
                    ⏱️ This code will expire in <strong>10 minutes</strong>.
                </p>
                <p style="color: #94A3B8; text-align: center; font-size: 13px;">
                    If you didn't create an account, you can safely ignore this email.
                </p>
                <div style="text-align: center; margin-top: 20px;">
                    <a href="https://foodie.com" class="btn">Visit FOODIE</a>
                </div>
                <div class="footer">
                    &copy; $year FOODIE. All rights reserved.
                    <br>
                    <small>This is an automated message, please do not reply.</small>
                </div>
            </div>
        </body>
        </html>
        HTML;
    }
}