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
    private ?string $customerName;
    private ?string $customerPhone;
    private ?string $deliveryAddress;
    private ?string $paymentMethod;

    public function __construct(
        ?int $id,
        int $userId,
        int $statusId,
        float $totalAmount,
        ?string $customerName = null,
        ?string $customerPhone = null,
        ?string $deliveryAddress = null,
        ?string $paymentMethod = null
    ) {
        $this->id = $id;
        $this->userId = $userId;
        $this->statusId = $statusId;
        $this->totalAmount = $totalAmount;
        $this->customerName = $customerName;
        $this->customerPhone = $customerPhone;
        $this->deliveryAddress = $deliveryAddress;
        $this->paymentMethod = $paymentMethod;
        $this->orderDate = new DateTime();
    }

    // Getters
    public function getId(): ?int { return $this->id; }
    public function getUserId(): int { return $this->userId; }
    public function getStatusId(): int { return $this->statusId; }
    public function getTotalAmount(): float { return $this->totalAmount; }
    public function getOrderDate(): DateTime { return $this->orderDate; }
    public function getCustomerName(): ?string { return $this->customerName; }
    public function getCustomerPhone(): ?string { return $this->customerPhone; }
    public function getDeliveryAddress(): ?string { return $this->deliveryAddress; }
    public function getPaymentMethod(): ?string { return $this->paymentMethod; }

    // Business Methods
    public function updateStatus(int $statusId): void
    {
        $this->statusId = $statusId;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'status_id' => $this->statusId,
            'total_amount' => $this->totalAmount,
            'order_date' => $this->orderDate->format('Y-m-d H:i:s'),
            'customer_name' => $this->customerName,
            'customer_phone' => $this->customerPhone,
            'delivery_address' => $this->deliveryAddress,
            'payment_method' => $this->paymentMethod
        ];
    }
}