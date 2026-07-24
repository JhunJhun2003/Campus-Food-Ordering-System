<?php
declare(strict_types=1);

namespace App\User\Presentation\Http\Controllers;

use App\User\Infrastructure\Repositories\UserRepository;
use App\User\Application\Usecases\LoginWithGoogleUseCase;
use App\User\Infrastructure\Services\GoogleAuthService;
use App\User\Application\Usecases\RegisterUserUseCase;
use App\User\Application\Usecases\LoginUserUseCase;
use App\User\Application\Usecases\GetProfileUseCase;
use App\User\Application\Usecases\UpdateProfileUseCase;
use App\User\Application\Usecases\SendVerificationUseCase;
use App\User\Application\Usecases\VerifyEmailUseCase;
use App\Security\Infrastructure\Services\GoogleRecaptchaService;

/**
 * User Controller Factory
 */
class UserControllerFactory
{
    private static ?UserController $instance = null;

    public static function create(): UserController
    {
        // Repositories
        $userRepository = new UserRepository();

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

        $getProfileUseCase = new GetProfileUseCase($userRepository);
        $updateProfileUseCase = new UpdateProfileUseCase($userRepository);
        $sendVerificationUseCase = new SendVerificationUseCase($userRepository);
        $verifyEmailUseCase = new VerifyEmailUseCase($userRepository);

        return new UserController(
            $userRepository,
            $registerUserUseCase,
            $loginUserUseCase,
            $getProfileUseCase,
            $updateProfileUseCase,
            $sendVerificationUseCase,
            $verifyEmailUseCase,
            $loginWithGoogleUseCase,
            $googleAuthService
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