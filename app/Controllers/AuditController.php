<?php
/** AuditController - read-only audit trail (director/school admin/auditor). */
final class AuditController
{
    public function index(): void
    {
        Auth::requireCan('audit.view');
        $db = Database::get();
        $schoolId = (int)Auth::schoolId();
        $action = trim((string)($_GET['action'] ?? ''));

        $sql = "SELECT a.*, u.name AS actor_name
                FROM audit_logs a
                LEFT JOIN users u ON u.id = a.actor_user_id
                WHERE a.school_id = :s";
        $params = ['s' => $schoolId];
        if ($action !== '') {
            $sql .= ' AND a.action LIKE :a';
            $params['a'] = "%{$action}%";
        }
        $sql .= ' ORDER BY a.id DESC LIMIT 200';

        render('audit/index', ['title' => 'Audit trail', 'logs' => $db->fetchAll($sql, $params), 'action' => $action]);
    }
}
