<?php
declare(strict_types=1);

namespace App\Payment\Presentation\Http\Controllers;

use App\Payment\Application\Usecases\CreatePaymentMethodUseCase;
use App\Payment\Application\Usecases\UpdatePaymentMethodUseCase;
use App\Payment\Application\Usecases\DeletePaymentMethodUseCase;
use App\Payment\Infrastructure\Repositories\PaymentRepository;

class PaymentControllerFactory
{
    private static ?PaymentController $instance = null;

    public static function create(): PaymentController
    {
        $paymentRepository = new PaymentRepository();
        
        $createPaymentMethodUseCase = new CreatePaymentMethodUseCase($paymentRepository);
        $updatePaymentMethodUseCase = new UpdatePaymentMethodUseCase($paymentRepository);
        $deletePaymentMethodUseCase = new DeletePaymentMethodUseCase($paymentRepository);

        return new PaymentController(
            $paymentRepository,
            $createPaymentMethodUseCase,
            $updatePaymentMethodUseCase,
            $deletePaymentMethodUseCase
        );
    }

    public static function getInstance(): PaymentController
    {
        if (self::$instance === null) {
            self::$instance = self::create();
        }
        return self::$instance;
    }
}