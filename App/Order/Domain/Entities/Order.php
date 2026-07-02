<?php
namespace App\Order\Domain\Entities;

use DateTime;

class Order
{
    private ?int $id;
    private int $userId;
    private int $statusId;
    private float $totalAmount;
    private DateTime $orderDate;
    private ?string $deliveryAddress;
    private ?string $paymentMethod;
    private ?string $customerName;
    private ?string $customerPhone;
    private ?string $accountName;
    private ?string $accountNumber;
    private ?string $transactionImage;

    public function __construct(
        ?int $id,
        int $userId,
        int $statusId,
        float $totalAmount,
        ?string $deliveryAddress = null,
        ?string $paymentMethod = null,
        ?string $customerName = null,
        ?string $customerPhone = null,
        ?string $accountName = null,
        ?string $accountNumber = null,
        ?string $transactionImage = null,
        ?DateTime $orderDate = null
    ) {
        $this->id = $id;
        $this->userId = $userId;
        $this->statusId = $statusId;
        $this->totalAmount = $totalAmount;
        $this->deliveryAddress = $deliveryAddress;
        $this->paymentMethod = $paymentMethod;
        $this->customerName = $customerName;
        $this->customerPhone = $customerPhone;
        $this->accountName = $accountName;
        $this->accountNumber = $accountNumber;
        $this->transactionImage = $transactionImage;
        $this->orderDate = $orderDate ?? new DateTime();
    }

    // Getters
    public function getId(): ?int { return $this->id; }
    public function getUserId(): int { return $this->userId; }
    public function getStatusId(): int { return $this->statusId; }
    public function getTotalAmount(): float { return $this->totalAmount; }
    public function getOrderDate(): DateTime { return $this->orderDate; }
    public function getDeliveryAddress(): ?string { return $this->deliveryAddress; }
    public function getPaymentMethod(): ?string { return $this->paymentMethod; }
    public function getCustomerName(): ?string { return $this->customerName; }
    public function getCustomerPhone(): ?string { return $this->customerPhone; }
    public function getAccountName(): ?string { return $this->accountName; }
    public function getAccountNumber(): ?string { return $this->accountNumber; }
    public function getTransactionImage(): ?string { return $this->transactionImage; }
}
