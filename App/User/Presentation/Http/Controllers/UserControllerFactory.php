<?php
declare(strict_types=1);

namespace App\User\Presentation\Http\Controllers;

use App\User\Infrastructure\Repositories\UserRepository;
use App\User\Infrastructure\Repositories\PasswordResetRepository;
use App\User\Application\Usecases\LoginWithGoogleUseCase;
use App\User\Infrastructure\Services\GoogleAuthService;
use App\User\Application\Usecases\RegisterUserUseCase;
use App\User\Application\Usecases\LoginUserUseCase;
use App\User\Application\Usecases\GetProfileUseCase;
use App\User\Application\Usecases\UpdateProfileUseCase;
use App\User\Application\Usecases\SendVerificationUseCase;
use App\User\Application\Usecases\VerifyEmailUseCase;
use App\Security\Infrastructure\Services\GoogleRecaptchaService;
use App\User\Application\Usecases\ForgotPasswordUseCase;
use App\User\Application\Usecases\VerifyResetCodeUseCase;
use App\User\Application\Usecases\ResetPasswordUseCase;

/**
 * User Controller Factory
 */
class UserControllerFactory
{
    private static ?UserController $instance = null;

    public static function create(): UserController
    {
        // ✅ Repositories
        $userRepository = new UserRepository();
        $passwordResetRepository = new PasswordResetRepository(); // ✅ CREATE THIS

        // ✅ reCAPTCHA Service
        $recaptchaService = new GoogleRecaptchaService();

        // Services
        $googleAuthService = new GoogleAuthService();

        // Use Cases
        $loginWithGoogleUseCase = new LoginWithGoogleUseCase(
            $googleAuthService,
            $userRepository
        );

        // ✅ RegisterUserUseCase with reCAPTCHA
        $registerUserUseCase = new RegisterUserUseCase(
            $userRepository,
            $recaptchaService
        );

        // ✅ LoginUserUseCase with reCAPTCHA
        $loginUserUseCase = new LoginUserUseCase(
            $userRepository,
            $recaptchaService
        );

        // ✅ ForgotPasswordUseCase
        $forgotPasswordUseCase = new ForgotPasswordUseCase(
            $userRepository,
            $passwordResetRepository, // ✅ Now defined
            $recaptchaService
        );

        // ✅ VerifyResetCodeUseCase
        $verifyResetCodeUseCase = new VerifyResetCodeUseCase(
            $passwordResetRepository
        );

        // ✅ ResetPasswordUseCase
        $resetPasswordUseCase = new ResetPasswordUseCase(
            $userRepository,
            $passwordResetRepository
        );

        $getProfileUseCase = new GetProfileUseCase($userRepository);
        $updateProfileUseCase = new UpdateProfileUseCase($userRepository);
        $sendVerificationUseCase = new SendVerificationUseCase($userRepository);
        $verifyEmailUseCase = new VerifyEmailUseCase($userRepository);

        // ✅ Return UserController with all dependencies
        return new UserController(
            $userRepository,
            $registerUserUseCase,
            $loginUserUseCase,
            $getProfileUseCase,
            $updateProfileUseCase,
            $sendVerificationUseCase,
            $verifyEmailUseCase,
            $loginWithGoogleUseCase,
            $googleAuthService,
            $forgotPasswordUseCase,      // ✅ New
            $verifyResetCodeUseCase,     // ✅ New
            $resetPasswordUseCase        // ✅ New
        );
    }

    public static function getInstance(): UserController
    {
        if (self::$instance === null) {
            self::$instance = self::create();
        }
        return self::$instance;
    }
}