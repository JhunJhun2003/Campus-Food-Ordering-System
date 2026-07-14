<?php
declare(strict_types=1);

namespace App\Order\Application\Services;

use Dompdf\Dompdf;
use Dompdf\Options;
use App\Order\Infrastructure\Repositories\OrderRepository;
use App\Refund\Infrastructure\Repositories\RefundRepository;

class ReceiptPdfService
{
    private OrderRepository $orderRepository;
    private RefundRepository $refundRepository;

    public function __construct()
    {
        $this->orderRepository = new OrderRepository();
        $this->refundRepository = new RefundRepository();
    }

    /**
     * Generate and stream the receipt PDF
     */
    public function generateReceiptPdf(int $orderId): void
    {
        $order = $this->orderRepository->findByIdWithDetails($orderId);
        if (!$order) {
            throw new \RuntimeException("Order not found", 404);
        }

        // Check for associated refund details
        $refund = $this->refundRepository->findByOrderId($orderId);

        $html = $this->buildReceiptHtml($order, $refund);

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'Helvetica');
        
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper([0, 0, 595.28, 841.89], 'portrait'); // A4 size in points
        $dompdf->render();

        // Stream the PDF inline
        $dompdf->stream("receipt_order_{$orderId}.pdf", ["Attachment" => false]);
    }

    /**
     * Build standard CSS styling and HTML body for receipt
     */
    private function buildReceiptHtml(array $order, ?\App\Refund\Domain\Entities\Refund $refund): string
    {
        $siteName = 'FOODIE';
        $orderDate = date('M d, Y h:i A', strtotime($order['order_date'] ?? $order['created_at']));
        $customerName = htmlspecialchars($order['customer_name'] ?: ($order['customer_name_from_user'] ?: 'Guest Customer'));
        $customerPhone = htmlspecialchars($order['customer_phone'] ?: ($order['customer_phone_from_user'] ?: 'N/A'));
        $address = htmlspecialchars($order['delivery_address'] ?? 'canteen pickup');
        
        $paymentMethod = htmlspecialchars($order['payment_method_name'] ?? 'Cash on Delivery');
        $paymentStatus = strtoupper(htmlspecialchars($order['payment_status_name'] ?? 'pending'));
        $transactionNo = htmlspecialchars($order['transaction_no'] ?? 'N/A');
        
        $orderStatus = strtoupper(htmlspecialchars($order['status_name'] ?? 'pending'));
        
        $itemsHtml = '';
        foreach ($order['items'] as $item) {
            $name = htmlspecialchars($item['food_name']);
            $qty = (int) $item['quantity'];
            $price = number_format((float) $item['unit_price'], 2);
            $subtotal = number_format((float) $item['subtotal'], 2);
            
            $itemsHtml .= "
                <tr>
                    <td class='desc'>{$name}</td>
                    <td class='qty'>{$qty}</td>
                    <td class='unit'>\${$price}</td>
                    <td class='total'>\${$subtotal}</td>
                </tr>
            ";
        }
        
        $totalAmount = number_format((float) $order['total_amount'], 2);

        // Refund info section (if refund is requested or completed)
        $refundSectionHtml = '';
        if ($refund !== null) {
            $statusName = match ($refund->getRefundStatusId()) {
                1 => 'PENDING REQUEST',
                2 => 'APPROVED / REFUNDED',
                3 => 'REJECTED / CANCELLED',
                4 => 'COMPLETED',
                default => 'UNKNOWN'
            };
            
            $statusColor = match ($refund->getRefundStatusId()) {
                1 => '#D97706', // Yellow/Amber
                2 => '#059669', // Green
                3 => '#DC2626', // Red
                4 => '#2563EB', // Blue
                default => '#4B5563'
            };
            
            $notes = $refund->getNotes() ? '<p><strong>Notes:</strong> ' . htmlspecialchars($refund->getNotes()) . '</p>' : '';
            $reason = htmlspecialchars($refund->getReason());
            $refundId = $refund->getId();
            
            $refundSectionHtml = "
                <div class='refund-box' style='border-left: 4px solid {$statusColor};'>
                    <h3 style='color: {$statusColor}; margin-top: 0;'>REFUND DETAILS</h3>
                    <table class='info-table'>
                        <tr>
                            <td style='width: 30%;'><strong>Refund ID:</strong></td>
                            <td>#{$refundId}</td>
                        </tr>
                        <tr>
                            <td><strong>Refund Status:</strong></td>
                            <td><span style='color: {$statusColor}; font-weight: bold;'>{$statusName}</span></td>
                        </tr>
                        <tr>
                            <td><strong>Reason:</strong></td>
                            <td>{$reason}</td>
                        </tr>
                    </table>
                    {$notes}
                </div>
            ";
        }

        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='utf-8'>
            <title>Receipt #{$order['id']}</title>
            <style>
                body {
                    font-family: 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif;
                    color: #333;
                    line-height: 1.4;
                    margin: 0;
                    padding: 0;
                }
                .invoice-box {
                    max-width: 800px;
                    margin: auto;
                    padding: 30px;
                    font-size: 14px;
                }
                .header-table {
                    width: 100%;
                    margin-bottom: 30px;
                    border-collapse: collapse;
                }
                .header-table td {
                    padding: 0;
                    vertical-align: top;
                }
                .brand {
                    font-size: 28px;
                    font-weight: bold;
                    letter-spacing: 2px;
                    color: #0f172a;
                }
                .title {
                    text-align: right;
                    font-size: 24px;
                    color: #475569;
                    font-weight: bold;
                }
                .info-table {
                    width: 100%;
                    margin-bottom: 30px;
                    border-collapse: collapse;
                }
                .info-table td {
                    padding: 4px 0;
                    vertical-align: top;
                }
                .details-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-bottom: 30px;
                }
                .details-table th {
                    background: #f8fafc;
                    border-bottom: 2px solid #e2e8f0;
                    font-weight: bold;
                    padding: 10px;
                    text-align: left;
                    font-size: 12px;
                    color: #475569;
                    text-transform: uppercase;
                }
                .details-table td {
                    padding: 12px 10px;
                    border-bottom: 1px solid #f1f5f9;
                    font-size: 13px;
                }
                .details-table td.desc {
                    text-align: left;
                }
                .details-table td.qty {
                    text-align: center;
                }
                .details-table td.unit, .details-table td.total {
                    text-align: right;
                }
                .summary-table {
                    width: 40%;
                    float: right;
                    border-collapse: collapse;
                    margin-bottom: 30px;
                }
                .summary-table td {
                    padding: 6px 10px;
                    font-size: 13px;
                }
                .summary-table tr.total-row td {
                    font-weight: bold;
                    font-size: 16px;
                    border-top: 2px solid #e2e8f0;
                    padding-top: 10px;
                    color: #0f172a;
                }
                .refund-box {
                    background-color: #fcfcfc;
                    border: 1px solid #e2e8f0;
                    padding: 15px;
                    border-radius: 6px;
                    margin-bottom: 30px;
                    clear: both;
                }
                .footer {
                    margin-top: 50px;
                    text-align: center;
                    font-size: 12px;
                    color: #94a3b8;
                    border-top: 1px solid #f1f5f9;
                    padding-top: 20px;
                    clear: both;
                }
            </style>
        </head>
        <body>
            <div class='invoice-box'>
                <table class='header-table'>
                    <tr>
                        <td>
                            <div class='brand'>{$siteName}</div>
                            <p style='margin: 5px 0 0 0; color: #64748b;'>Campus Online Food Ordering System</p>
                        </td>
                        <td>
                            <div class='title'>RECEIPT / INVOICE</div>
                            <p style='text-align: right; margin: 5px 0 0 0; color: #64748b;'>
                                <strong>Order ID:</strong> #{$order['id']}<br>
                                <strong>Date:</strong> {$orderDate}
                            </p>
                        </td>
                    </tr>
                </table>

                <table class='info-table'>
                    <tr>
                        <td style='width: 50%;'>
                            <h3 style='color: #475569; margin: 0 0 8px 0; font-size: 14px; text-transform: uppercase;'>Customer Details</h3>
                            <strong>Name:</strong> {$customerName}<br>
                            <strong>Phone:</strong> {$customerPhone}<br>
                            <strong>Delivery Address:</strong> {$address}
                        </td>
                        <td style='width: 50%; padding-left: 20px;'>
                            <h3 style='color: #475569; margin: 0 0 8px 0; font-size: 14px; text-transform: uppercase;'>Order & Payment Info</h3>
                            <strong>Order Status:</strong> {$orderStatus}<br>
                            <strong>Payment Method:</strong> {$paymentMethod}<br>
                            <strong>Payment Status:</strong> {$paymentStatus}<br>
                            <strong>Transaction No:</strong> {$transactionNo}
                        </td>
                    </tr>
                </table>

                {$refundSectionHtml}

                <table class='details-table'>
                    <thead>
                        <tr>
                            <th class='desc' style='width: 50%;'>Item Description</th>
                            <th class='qty' style='width: 10%; text-align: center;'>Qty</th>
                            <th class='unit' style='width: 20%; text-align: right;'>Unit Price</th>
                            <th class='total' style='width: 20%; text-align: right;'>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        {$itemsHtml}
                    </tbody>
                </table>

                <table class='summary-table'>
                    <tr class='total-row'>
                        <td>Amount Paid:</td>
                        <td style='text-align: right;'>\${$totalAmount}</td>
                    </tr>
                </table>

                <div class='footer'>
                    <p>Thank you for your order! If you have any issues, please contact site support.</p>
                    <p style='font-size: 10px;'>This is a computer-generated receipt.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
}
