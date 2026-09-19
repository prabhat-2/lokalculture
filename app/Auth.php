<?php

declare(strict_types=1);

final class Auth
{
    public function __construct(private PDO $connection)
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    public function csrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    public function checkCsrf(?string $token): bool
    {
        return is_string($token) && hash_equals($_SESSION['csrf_token'] ?? '', $token);
    }

    public function registerCustomer(string $name, string $email, string $password): array
    {
        $role = $this->connection->query("SELECT id FROM roles WHERE name = 'customer'")->fetch();
        if (!$role) {
            return ['error' => 'Customer role is not configured.'];
        }

        $statement = $this->connection->prepare(
            'INSERT INTO users (role_id, name, email, password_hash) VALUES (:role_id, :name, :email, :password_hash)'
        );

        try {
            $statement->execute([
                'role_id' => $role['id'],
                'name' => $name,
                'email' => strtolower($email),
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            ]);
        } catch (PDOException $exception) {
            if ((int) ($exception->errorInfo[1] ?? 0) === 1062) {
                return ['error' => 'An account with this email already exists.'];
            }

            throw $exception;
        }

        $this->loginUser((int) $this->connection->lastInsertId());
        return ['user' => $this->currentUser()];
    }

    public function registerVendor(string $name, string $email, string $password, array $vendorData): array
    {
        $role = $this->connection->query("SELECT id FROM roles WHERE name = 'vendor'")->fetch();
        if (!$role) {
            return ['error' => 'Vendor role is not configured.'];
        }

        $this->connection->beginTransaction();
        try {
            $user = $this->connection->prepare(
                'INSERT INTO users (role_id, name, email, password_hash) VALUES (:role_id, :name, :email, :password_hash)'
            );
            $user->execute([
                'role_id' => $role['id'],
                'name' => $name,
                'email' => strtolower($email),
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            ]);
            $userId = (int) $this->connection->lastInsertId();
            $slug = strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '-', $vendorData['shop_name']), '-')) . '-' . strtolower(substr(bin2hex(random_bytes(3)), 0, 6));
            $vendor = $this->connection->prepare(
                "INSERT INTO vendors (user_id, shop_name, slug, description, state, region, craft_specialty, gstin, gst_status, registered_address, postal_code, verification_status, is_active)
                 VALUES (:user_id, :shop_name, :slug, :description, :state, :region, :craft_specialty, :gstin, :gst_status, :registered_address, :postal_code, 'pending', 0)"
            );
            $vendor->execute([
                'user_id' => $userId,
                'shop_name' => $vendorData['shop_name'],
                'slug' => $slug,
                'description' => $vendorData['description'],
                'state' => $vendorData['state'],
                'region' => $vendorData['region'],
                'craft_specialty' => $vendorData['craft_specialty'],
                'gstin' => $vendorData['gstin'],
                'gst_status' => $vendorData['gstin'] !== '' ? 'pending' : 'not_provided',
                'registered_address' => $vendorData['registered_address'],
                'postal_code' => $vendorData['postal_code'],
            ]);
            $this->connection->commit();
            return ['pending' => true];
        } catch (PDOException $exception) {
            $this->connection->rollBack();
            if ((int) ($exception->errorInfo[1] ?? 0) === 1062) {
                return ['error' => 'An account with this email already exists.'];
            }
            throw $exception;
        } catch (Throwable $exception) {
            $this->connection->rollBack();
            throw $exception;
        }
    }

    public function attempt(string $email, string $password): bool
    {
        $statement = $this->connection->prepare(
            'SELECT u.id, u.password_hash FROM users u WHERE u.email = :email LIMIT 1'
        );
        $statement->execute(['email' => strtolower($email)]);
        $user = $statement->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return false;
        }

        $this->loginUser((int) $user['id']);
        return true;
    }

    public function currentUser(): ?array
    {
        if (empty($_SESSION['user_id'])) {
            return null;
        }

        $statement = $this->connection->prepare(
            'SELECT u.id, u.name, u.email, r.name AS role FROM users u INNER JOIN roles r ON r.id = u.role_id WHERE u.id = :id LIMIT 1'
        );
        $statement->execute(['id' => $_SESSION['user_id']]);
        return $statement->fetch() ?: null;
    }

    public function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool) $params['secure'], (bool) $params['httponly']);
        }
        session_destroy();
    }

    private function loginUser(int $userId): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $userId;
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}