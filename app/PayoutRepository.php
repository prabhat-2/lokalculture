<?php

declare(strict_types=1);

final class PayoutRepository
{
    public function __construct(private PDO $connection)
    {
    }

    public function vendor(int $userId): ?array
    {
        $statement = $this->connection->prepare('SELECT id, shop_name AS name, state, commission_rate FROM vendors WHERE user_id = :user_id LIMIT 1');
        $statement->execute(['user_id' => $userId]);
        return $statement->fetch() ?: null;
    }

    public function saveVendorState(int $vendorId, string $state): void
    {
        $statement = $this->connection->prepare('UPDATE vendors SET state = :state WHERE id = :id');
        $statement->execute(['state' => $state, 'id' => $vendorId]);
    }

    public function bankDetails(int $vendorId): ?array
    {
        $statement = $this->connection->prepare('SELECT account_holder, bank_name, account_number_masked, ifsc_code FROM vendor_bank_details WHERE vendor_id = :vendor_id LIMIT 1');
        $statement->execute(['vendor_id' => $vendorId]);
        return $statement->fetch() ?: null;
    }

    public function saveBankDetails(int $vendorId, array $data): void
    {
        $statement = $this->connection->prepare(
            'INSERT INTO vendor_bank_details (vendor_id, account_holder, bank_name, account_number_masked, ifsc_code)
             VALUES (:vendor_id, :account_holder, :bank_name, :account_number_masked, :ifsc_code)
             ON DUPLICATE KEY UPDATE account_holder = VALUES(account_holder), bank_name = VALUES(bank_name), account_number_masked = VALUES(account_number_masked), ifsc_code = VALUES(ifsc_code)'
        );
        $statement->execute($data + ['vendor_id' => $vendorId]);
    }

    public function earnings(int $vendorId): array
    {
        $statement = $this->connection->prepare(
            "SELECT COALESCE(SUM((oi.unit_price * oi.quantity) - oi.commission_total), 0) AS gross_earnings,
                    COALESCE(SUM(oi.commission_total), 0) AS platform_commission,
                    COUNT(DISTINCT oi.order_id) AS order_count
             FROM order_items oi INNER JOIN orders o ON o.id = oi.order_id
             WHERE oi.vendor_id = :vendor_id AND o.status IN ('paid', 'processing', 'shipped', 'delivered')"
        );
        $statement->execute(['vendor_id' => $vendorId]);
        return $statement->fetch() ?: ['gross_earnings' => 0, 'platform_commission' => 0, 'order_count' => 0];
    }

    public function eligibleItems(int $vendorId): array
    {
        $statement = $this->connection->prepare(
            "SELECT oi.id, oi.order_id, oi.product_name, (oi.unit_price * oi.quantity) - oi.commission_total AS amount
             FROM order_items oi INNER JOIN orders o ON o.id = oi.order_id
             LEFT JOIN payout_items pi ON pi.order_item_id = oi.id
             WHERE oi.vendor_id = :vendor_id AND o.status IN ('paid', 'processing', 'shipped', 'delivered') AND pi.id IS NULL
             ORDER BY o.created_at"
        );
        $statement->execute(['vendor_id' => $vendorId]);
        return $statement->fetchAll();
    }

    public function payouts(?int $vendorId = null): array
    {
        $sql = 'SELECT p.id, p.amount, p.status, p.note, p.requested_at, v.shop_name AS vendor FROM payouts p INNER JOIN vendors v ON v.id = p.vendor_id';
        $parameters = [];
        if ($vendorId !== null) {
            $sql .= ' WHERE p.vendor_id = :vendor_id';
            $parameters['vendor_id'] = $vendorId;
        }
        $sql .= ' ORDER BY p.requested_at DESC';
        $statement = $this->connection->prepare($sql);
        $statement->execute($parameters);
        return $statement->fetchAll();
    }

    public function request(int $vendorId): int
    {
        $items = $this->eligibleItems($vendorId);
        if (!$items) {
            throw new RuntimeException('There are no eligible earnings to request.');
        }
        $amount = array_sum(array_column($items, 'amount'));
        $this->connection->beginTransaction();
        try {
            $payout = $this->connection->prepare("INSERT INTO payouts (vendor_id, amount, status) VALUES (:vendor_id, :amount, 'pending')");
            $payout->execute(['vendor_id' => $vendorId, 'amount' => $amount]);
            $payoutId = (int) $this->connection->lastInsertId();
            $item = $this->connection->prepare('INSERT INTO payout_items (payout_id, order_item_id, amount) VALUES (:payout_id, :order_item_id, :amount)');
            foreach ($items as $eligible) {
                $item->execute(['payout_id' => $payoutId, 'order_item_id' => $eligible['id'], 'amount' => $eligible['amount']]);
            }
            $this->connection->commit();
            return $payoutId;
        } catch (Throwable $exception) {
            $this->connection->rollBack();
            throw $exception;
        }
    }

    public function review(int $payoutId, string $status): void
    {
        if (!in_array($status, ['approved', 'rejected', 'paid'], true)) {
            throw new InvalidArgumentException('Unsupported payout status.');
        }
        $this->connection->beginTransaction();
        try {
            if ($status === 'rejected') {
                $release = $this->connection->prepare('DELETE FROM payout_items WHERE payout_id = :payout_id');
                $release->execute(['payout_id' => $payoutId]);
            }
            $statement = $this->connection->prepare('UPDATE payouts SET status = :status, reviewed_at = CURRENT_TIMESTAMP WHERE id = :id AND status IN (\'pending\', \'approved\')');
            $statement->execute(['status' => $status, 'id' => $payoutId]);
            if ($statement->rowCount() !== 1) {
                throw new RuntimeException('This payout can no longer be reviewed.');
            }
            $this->connection->commit();
        } catch (Throwable $exception) {
            $this->connection->rollBack();
            throw $exception;
        }
    }
}