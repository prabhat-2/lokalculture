<?php

declare(strict_types=1);

final class NotificationService
{
    public function __construct(private PDO $connection)
    {
    }

    public function orderUpdate(int $userId, string $email, string $subject, string $body): void
    {
        $statement = $this->connection->prepare('INSERT INTO notifications (user_id, type, subject, body, sent_at) VALUES (:user_id, \'order\', :subject, :body, :sent_at)');
        $from = (string) (getenv('SMTP_FROM_EMAIL') ?: 'no-reply@lokalculture.test');
        $sent = mail($email, $subject, $body, 'From: Lokal Culture <' . $from . ">\r\nContent-Type: text/plain; charset=UTF-8");
        $statement->execute(['user_id' => $userId, 'subject' => $subject, 'body' => $body, 'sent_at' => $sent ? date('Y-m-d H:i:s') : null]);
    }

    public function paymentStatus(int $userId, string $email, string $orderNumber, string $status): void
    {
        $this->orderUpdate($userId, $email, 'Payment ' . $status . ': ' . $orderNumber, 'Payment status for order ' . $orderNumber . ': ' . $status . '.');
    }

    public function shipmentUpdate(int $userId, string $email, string $orderNumber, string $status): void
    {
        $this->orderUpdate($userId, $email, 'Shipment update: ' . $orderNumber, 'Your order ' . $orderNumber . ' is now ' . $status . '.');
    }

    public function refundStatus(int $userId, string $email, string $orderNumber, string $status): void
    {
        $this->orderUpdate($userId, $email, 'Refund ' . $status . ': ' . $orderNumber, 'Refund status for order ' . $orderNumber . ': ' . $status . '.');
    }
}