<?php
/**
 * NotificationService - asynchronous, queued notifications.
 * Core payment posting NEVER depends on notification delivery success.
 */
final class NotificationService
{
    /**
     * Queue a notification (parent or staff). Returns the notification id.
     */
    public static function queue(
        ?int $schoolId,
        string $channel,
        string $subject,
        string $body,
        ?int $userId = null,
        ?string $receiver = null
    ): int {
        return Database::get()->insert('notifications', [
            'school_id' => $schoolId,
            'user_id'   => $userId,
            'receiver'  => $receiver,
            'channel'   => $channel,
            'subject'   => $subject,
            'body'      => $body,
            'status'    => 'queued',
            'attempts'  => 0,
        ]);
    }

    /**
     * Try to deliver queued notifications independently.
     * Simulated delivery (stub for a real SMS/Email adapter); retries on failure.
     */
    public static function processQueue(int $batch = 20): int
    {
        $db = Database::get();
        $rows = $db->fetchAll(
            "SELECT * FROM notifications WHERE status = 'queued' ORDER BY id ASC LIMIT {$batch}"
        );
        $sent = 0;
        foreach ($rows as $n) {
            // Delivery stub - replace with a real SMS/Email provider adapter.
            // Simulated: delivery succeeds unless the body requests a forced failure.
            $ok = !str_contains($n['body'] ?? '', '__SIMULATE_FAIL__');
            if (!$ok) {
                $db->update('notifications', [
                    'attempts' => $n['attempts'] + 1,
                    'status'   => $n['attempts'] + 1 >= 5 ? 'failed' : 'queued',
                ], 'id = :id', ['id' => $n['id']]);
            } else {
                $db->update('notifications', [
                    'status'   => 'sent',
                    'attempts' => $n['attempts'] + 1,
                    'sent_at'  => date('Y-m-d H:i:s'),
                ], 'id = :id', ['id' => $n['id']]);
                $sent++;
            }
        }
        return $sent;
    }

    public static function inbox(int $userId): array
    {
        return Database::get()->fetchAll(
            "SELECT * FROM notifications WHERE user_id = :uid
             ORDER BY id DESC LIMIT 50",
            ['uid' => $userId]
        );
    }
}
