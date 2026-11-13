<?php
declare(strict_types=1);

/**
 * "Beni hatırla" kalıcı oturum anahtarları.
 *
 * Çerezde selector + validator birlikte taşınır; veritabanında yalnızca
 * validator'ın SHA-256 özeti saklanır. Böylece veritabanı sızsa bile
 * çerezdeki değerle oturum açılamaz.
 */
final class RememberToken
{
    /** @return array{selector: string, validator: string} */
    public static function create(int $userId, int $ttl): array
    {
        $pdo = Database::get();
        $now = time();

        // Hafif bakım: süresi geçmiş anahtarları temizle
        $pdo->prepare('DELETE FROM remember_tokens WHERE expires_at < ?')->execute([$now]);

        $selector = bin2hex(random_bytes(12));
        $validator = bin2hex(random_bytes(32));

        $stmt = $pdo->prepare(
            'INSERT INTO remember_tokens (user_id, selector, validator_hash, created_at, expires_at)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$userId, $selector, hash('sha256', $validator), $now, $now + $ttl]);

        return ['selector' => $selector, 'validator' => $validator];
    }

    public static function find(string $selector): ?array
    {
        $stmt = Database::get()->prepare(
            'SELECT id, user_id, validator_hash, expires_at FROM remember_tokens WHERE selector = ?'
        );
        $stmt->execute([$selector]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function delete(int $id): void
    {
        Database::get()->prepare('DELETE FROM remember_tokens WHERE id = ?')->execute([$id]);
    }

    public static function deleteBySelector(string $selector): void
    {
        Database::get()->prepare('DELETE FROM remember_tokens WHERE selector = ?')->execute([$selector]);
    }

    public static function deleteAllForUser(int $userId): void
    {
        Database::get()->prepare('DELETE FROM remember_tokens WHERE user_id = ?')->execute([$userId]);
    }
}
