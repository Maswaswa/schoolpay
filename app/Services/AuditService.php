<?php
/** AuditService - append-only record of high-risk / financial actions. */
final class AuditService
{
    public static function log(
        ?int $schoolId,
        ?int $userId,
        string $action,
        string $entity = '',
        ?int $entityId = null,
        array $details = []
    ): void {
        // school ids start at 1; 0/null mean "platform level" — store NULL so the
        // audit_logs.school_id FK to schools(id) is never violated.
        if ($schoolId !== null && $schoolId <= 0) {
            $schoolId = null;
        }
        Database::get()->insert('audit_logs', [
            'school_id' => $schoolId,
            'user_id'   => $userId,
            'action'    => $action,
            'entity'    => $entity,
            'entity_id' => $entityId,
            'details'   => json_encode($details, JSON_UNESCAPED_SLASHES),
            'ip'        => $_SERVER['REMOTE_ADDR'] ?? 'cli',
        ]);
    }

    public static function all(?int $schoolId, ?string $action = null, int $limit = 200): array
    {
        $where = '1=1';
        $params = [];
        if ($schoolId !== null) {
            $where .= ' AND school_id = :school_id';
            $params['school_id'] = $schoolId;
        }
        if ($action) {
            $where .= ' AND action = :action';
            $params['action'] = $action;
        }
        return Database::get()->fetchAll(
            "SELECT a.*, u.name AS user_name FROM audit_logs a
             LEFT JOIN users u ON u.id = a.user_id
             WHERE {$where} ORDER BY a.id DESC LIMIT :lim",
            $params + ['lim' => $limit]
        );
    }
}
