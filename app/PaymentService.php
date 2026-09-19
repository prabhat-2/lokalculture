<?php

declare(strict_types=1);

final class PaymentService
{
    private string $keyId;
    private string $keySecret;

    public function __construct(private PDO $connection)
    {
        $this->keyId = (string) (getenv('RAZORPAY_KEY_ID') ?: '');
        $this->keySecret = (string) (getenv('RAZORPAY_KEY_SECRET') ?: '');
    }

    public function createGatewayOrder(int $orderId, string $orderNumber, float $amount): array
    {
        if ($this->keyId === '' || $this->keySecret === '') {
            throw new RuntimeException('Razorpay keys are not configured. Add RAZORPAY_KEY_ID and RAZORPAY_KEY_SECRET to your environment.');
        }
        $payload = json_encode(['amount' => (int) round($amount * 100), 'currency' => 'INR', 'receipt' => $orderNumber, 'payment_capture' => 1], JSON_THROW_ON_ERROR);
        $response = $this->request('https://api.razorpay.com/v1/orders', $payload);
        if (empty($response['id'])) {
            throw new RuntimeException('Razorpay did not return a payment order.');
        }
        $statement = $this->connection->prepare("INSERT INTO payments (order_id, provider, provider_reference, amount, status, payload) VALUES (:order_id, 'razorpay', :provider_reference, :amount, 'created', :payload)");
        $statement->execute(['order_id' => $orderId, 'provider_reference' => $response['id'], 'amount' => $amount, 'payload' => json_encode($response, JSON_THROW_ON_ERROR)]);
        return ['id' => $response['id'], 'amount' => (int) round($amount * 100), 'currency' => 'INR'];
    }

    public function publicKey(): string
    {
        return $this->keyId;
    }

    public function handleWebhook(string $payload, string $signature): bool
    {
        if ($this->keySecret === '' || !hash_equals(hash_hmac('sha256', $payload, $this->keySecret), $signature)) {
            return false;
        }
        $event = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        $eventId = trim((string) ($event['id'] ?? ''));
        $eventType = trim((string) ($event['event'] ?? ''));
        $orderReference = trim((string) ($event['payload']['payment']['entity']['order_id'] ?? ''));
        if ($eventId === '' || $orderReference === '') {
            return false;
        }
        $this->connection->beginTransaction();
        try {
            $eventInsert = $this->connection->prepare('INSERT INTO payment_webhook_events (event_id, event_type, payload) VALUES (:event_id, :event_type, :payload)');
            try {
                $eventInsert->execute(['event_id' => $eventId, 'event_type' => $eventType, 'payload' => $payload]);
            } catch (PDOException $exception) {
                if ((int) ($exception->errorInfo[1] ?? 0) === 1062) {
                    $this->connection->rollBack();
                    return true;
                }
                throw $exception;
            }
            $status = $eventType === 'payment.captured' ? 'captured' : ($eventType === 'payment.failed' ? 'failed' : null);
            if ($status !== null) {
                $payment = $this->connection->prepare('SELECT order_id FROM payments WHERE provider = \'razorpay\' AND provider_reference = :reference LIMIT 1');
                $payment->execute(['reference' => $orderReference]);
                $paymentRow = $payment->fetch();
                if ($paymentRow) {
                    $update = $this->connection->prepare('UPDATE payments SET status = :status, payload = :payload WHERE order_id = :order_id AND status IN (\'created\', \'authorized\')');
                    $update->execute(['status' => $status, 'payload' => $payload, 'order_id' => $paymentRow['order_id']]);
                    if ($status === 'captured') {
                        $order = $this->connection->prepare("UPDATE orders SET status = 'paid' WHERE id = :id AND status = 'pending'");
                        $order->execute(['id' => $paymentRow['order_id']]);
                    }
                }
            }
            $this->connection->prepare('UPDATE payment_webhook_events SET processed_at = CURRENT_TIMESTAMP WHERE event_id = :event_id')->execute(['event_id' => $eventId]);
            $this->connection->commit();
            return true;
        } catch (Throwable $exception) {
            $this->connection->rollBack();
            throw $exception;
        }
    }

    public function refundOrder(int $orderId): void
    {
        if ($this->keyId === '' || $this->keySecret === '') {
            throw new RuntimeException('Razorpay keys are not configured.');
        }
        $statement = $this->connection->prepare(
            "SELECT p.provider_reference, p.amount FROM payments p INNER JOIN orders o ON o.id = p.order_id
             WHERE p.order_id = :order_id AND p.provider = 'razorpay' AND p.status = 'captured' AND o.status = 'paid' LIMIT 1"
        );
        $statement->execute(['order_id' => $orderId]);
        $payment = $statement->fetch();
        if (!$payment) {
            throw new RuntimeException('Only captured paid orders can be refunded.');
        }

        $payload = json_encode(['amount' => (int) round((float) $payment['amount'] * 100)], JSON_THROW_ON_ERROR);
        $response = $this->request(
            'https://api.razorpay.com/v1/payments/' . rawurlencode((string) $payment['provider_reference']) . '/refund',
            $payload
        );
        if (empty($response['id'])) {
            throw new RuntimeException('Razorpay did not return a refund reference.');
        }

        $this->connection->beginTransaction();
        try {
            $paymentUpdate = $this->connection->prepare("UPDATE payments SET status = 'refunded', payload = :payload WHERE order_id = :order_id AND status = 'captured'");
            $paymentUpdate->execute(['payload' => json_encode($response, JSON_THROW_ON_ERROR), 'order_id' => $orderId]);
            $orderUpdate = $this->connection->prepare("UPDATE orders SET status = 'refunded' WHERE id = :id AND status = 'paid'");
            $orderUpdate->execute(['id' => $orderId]);
            if ($orderUpdate->rowCount() !== 1) {
                throw new RuntimeException('The order is no longer eligible for refund.');
            }
            $items = $this->connection->prepare('SELECT product_id, quantity FROM order_items WHERE order_id = :order_id');
            $items->execute(['order_id' => $orderId]);
            $restore = $this->connection->prepare('UPDATE inventory SET available_quantity = available_quantity + :available, reserved_quantity = GREATEST(reserved_quantity - :reserved, 0) WHERE product_id = :product_id');
            foreach ($items->fetchAll() as $item) {
                $restore->execute(['available' => $item['quantity'], 'reserved' => $item['quantity'], 'product_id' => $item['product_id']]);
            }
            $this->connection->commit();
            $returnUpdate = $this->connection->prepare("UPDATE returns SET status = 'refunded', reviewed_at = COALESCE(reviewed_at, CURRENT_TIMESTAMP) WHERE order_id = :order_id AND status = 'approved'");
            $returnUpdate->execute(['order_id' => $orderId]);
        } catch (Throwable $exception) {
            $this->connection->rollBack();
            throw $exception;
        }
    }

    public function verifyAndCapture(int $userId, string $orderNumber, string $paymentId, string $gatewayOrderId, string $signature): bool
    {
        $statement = $this->connection->prepare("SELECT o.id, p.provider_reference FROM orders o INNER JOIN payments p ON p.order_id = o.id WHERE o.customer_id = :customer_id AND o.order_number = :order_number AND o.status = 'pending' AND p.provider = 'razorpay' LIMIT 1");
        $statement->execute(['customer_id' => $userId, 'order_number' => $orderNumber]);
        $order = $statement->fetch();
        if (!$order || !hash_equals((string) $order['provider_reference'], $gatewayOrderId)) {
            return false;
        }
        $expected = hash_hmac('sha256', $gatewayOrderId . '|' . $paymentId, $this->keySecret);
        if (!hash_equals($expected, $signature)) {
            return false;
        }
        $this->connection->beginTransaction();
        try {
            $payment = $this->connection->prepare("UPDATE payments SET provider_reference = :payment_reference, status = 'captured' WHERE order_id = :order_id AND status = 'created'");
            $payment->execute(['payment_reference' => $paymentId, 'order_id' => $order['id']]);
            $orderUpdate = $this->connection->prepare("UPDATE orders SET status = 'paid' WHERE id = :id AND status = 'pending'");
            $orderUpdate->execute(['id' => $order['id']]);
            $clear = $this->connection->prepare('DELETE ci FROM cart_items ci INNER JOIN carts c ON c.id = ci.cart_id WHERE c.user_id = :user_id');
            $clear->execute(['user_id' => $userId]);
            $this->connection->commit();
            return true;
        } catch (Throwable $exception) {
            $this->connection->rollBack();
            throw $exception;
        }
    }

    private function request(string $url, string $payload): array
    {
        $handle = curl_init($url);
        curl_setopt_array($handle, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $payload, CURLOPT_RETURNTRANSFER => true, CURLOPT_HTTPHEADER => ['Content-Type: application/json'], CURLOPT_USERPWD => $this->keyId . ':' . $this->keySecret, CURLOPT_TIMEOUT => 15]);
        $body = curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
        $error = curl_error($handle);
        curl_close($handle);
        if ($body === false || $status < 200 || $status >= 300) {
            throw new RuntimeException($error !== '' ? $error : 'Razorpay rejected the payment order request.');
        }
        return json_decode($body, true, 512, JSON_THROW_ON_ERROR);
    }
}