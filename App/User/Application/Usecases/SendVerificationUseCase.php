<?php
namespace App\User\Application\Usecases;

use App\User\Domain\Repositories\UserRepositoryInterface;
use App\User\Domain\ValueObjects\UserId;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class SendVerificationUseCase
{
    private UserRepositoryInterface $userRepository;

    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function execute(int $userId): array
    {
        $user = $this->userRepository->findById(new UserId($userId));
        
        if (!$user) {
            return ['success' => false, 'message' => 'User not found.'];
        }

        if ($user->isVerified()) {
            return ['success' => false, 'message' => 'Email already verified.'];
        }

        // Generate 4-digit code
        $code = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        
        // Set verification code on user
        $user->setVerificationCode($code, 10); // 10 minutes expiry
        
        // Save user
        $this->userRepository->save($user);

        // Send email
        $emailSent = $this->sendVerificationEmail(
            $user->getEmail()->getValue(),
            $user->getName(),
            $code
        );

        if (!$emailSent) {
            // Clear verification code if email fails
            $user->clearVerificationCode();
            $this->userRepository->save($user);
            return ['success' => false, 'message' => 'Failed to send verification email. Please try again.'];
        }

        return [
            'success' => true,
            'message' => 'Verification code sent to your email.',
            'code' => $code // For testing only
        ];
    }

    private function sendVerificationEmail(string $email, string $name, string $code): bool
    {
        try {
            $mail = new PHPMailer(true);
            
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
            
            $mail->setFrom('kokyaw3482@gmail.com', 'FOODIE');
            $mail->addAddress($email, $name);
            $mail->addReplyTo('kokyaw3482@gmail.com', 'FOODIE Support');
            
            $mail->isHTML(true);
            $mail->Subject = '🔐 Verify Your Foodie Account';
            $mail->Body    = $this->getEmailTemplate($name, $code);
            $mail->AltBody = "Your verification code is: $code\n\nThis code will expire in 10 minutes.";
            
            $mail->send();
            return true;
            
        } catch (Exception $e) {
            error_log('PHPMailer Error: ' . $e->getMessage());
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
            <style>
                body { font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px; }
                .container { max-width: 500px; margin: 0 auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
                .logo { text-align: center; font-size: 28px; font-weight: 800; color: #10B981; }
                .code { font-size: 36px; font-weight: 700; letter-spacing: 8px; text-align: center; padding: 20px; background: #F8FAFC; border-radius: 8px; margin: 20px 0; border: 2px dashed #E2E8F0; }
                .footer { text-align: center; color: #94A3B8; font-size: 12px; margin-top: 20px; border-top: 1px solid #E2E8F0; padding-top: 20px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="logo">🍔 FOODIE</div>
                <h2 style="text-align: center;">Verify Your Email</h2>
                <p>Hello <strong>$name</strong>,</p>
                <p>Use the verification code below to complete your registration:</p>
                <div class="code">$code</div>
                <p>⏱️ This code will expire in <strong>10 minutes</strong>.</p>
                <p>If you didn't create an account, you can safely ignore this email.</p>
                <div class="footer">&copy; $year FOODIE. All rights reserved.</div>
            </div>
        </body>
        </html>
        HTML;
    }
}