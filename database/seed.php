<?php
/**
 * Installer / seeder - creates the schema and seeds demo data.
 * Run via browser: /install   OR  CLI: php database/seed.php
 */

require_once __DIR__ . '/../app/bootstrap.php';

function run_schema(): void
{
    $sql = file_get_contents(SCHEMA_PATH);

    // Drop full-line comments first, so a leading "--" comment can never
    // cause the whole following CREATE statement to be skipped.
    $lines = [];
    foreach (preg_split('/\R/', $sql) as $line) {
        if (preg_match('/^\s*--/', $line)) continue;
        $lines[] = $line;
    }

    foreach (explode(';', implode("\n", $lines)) as $stmt) {
        $stmt = trim($stmt);
        if ($stmt === '') {
            continue;
        }
        try {
            Database::get()->run($stmt);
        } catch (Throwable $e) {
            // Ignore "already exists" style errors on re-runs
        }
    }
}

function seed_if_empty(): bool|array
{
    $count = (int)Database::get()->scalar('SELECT COUNT(*) FROM schools');
    if ($count > 0) {
        return false;
    }
    $db = Database::get();

    // ---- Roles ----
    $roles = [
        'platform_admin' => 'Platform Administrator', 'director' => 'School Owner / Director',
        'school_admin'   => 'School Administrator', 'bursar' => 'Bursar',
        'accountant'     => 'Accountant', 'cashier' => 'Cashier', 'teacher' => 'Teacher',
        'parent'         => 'Parent / Guardian', 'auditor' => 'Auditor', 'support' => 'Support Officer',
    ];
    foreach ($roles as $code => $name) {
        $db->insert('roles', ['code' => $code, 'name' => $name]);
    }
    $roleId = fn(string $code) => (int)$db->scalar("SELECT id FROM roles WHERE code = :c", ['c' => $code]);

    // ---- Platform admin (school_id NULL) ----
    $adminId = $db->insert('users', [
        'school_id'     => null,
        'name'          => 'Platform Administrator',
        'email'         => 'platform@schoolfees.test',
        'password_hash' => password_hash('password123', PASSWORD_DEFAULT),
    ]);
    $db->insert('user_roles', ['user_id' => $adminId, 'role_id' => $roleId('platform_admin')]);

    // ---- School (tenant) ----
    $schoolId = $db->insert('schools', ['code' => 'KFS', 'name' => 'Kampala Future School']);
    $db->insert('campuses', ['school_id' => $schoolId, 'name' => 'Main Campus', 'address' => 'Kampala, Uganda']);

    $mk = function (string $name, string $email, string $role) use ($db, $roleId, $schoolId) {
        $id = $db->insert('users', [
            'school_id'     => $schoolId,
            'name'          => $name,
            'email'         => $email,
            'phone'         => '2567' . random_int(10000000, 99999999),
            'password_hash' => password_hash('password123', PASSWORD_DEFAULT),
        ]);
        $db->insert('user_roles', ['user_id' => $id, 'role_id' => $roleId($role)]);
        return $id;
    };

    $directorId    = $mk('Grace Auma',     'director@kfs.test',   'director');
    $schoolAdminId = $mk('Peter Okello',   'admin@kfs.test',      'school_admin');
    $bursarId      = $mk('Sarah Namutebi', 'bursar@kfs.test',     'bursar');
    $accountantId  = $mk('John Mugisha',   'accountant@kfs.test', 'accountant');
    $cashierId     = $mk('Alice Nakato',   'cashier@kfs.test',    'cashier');
    $teacherId     = $mk('Ronald Ssemakula', 'teacher@kfs.test',  'teacher');
    $auditorId     = $mk('Doreen Atim',    'auditor@kfs.test',    'auditor');
    $parentId      = $mk('Mary Sebbi',     'parent@kfs.test',     'parent');

    // ---- Academic ----
    $yearId = $db->insert('academic_years', ['school_id' => $schoolId, 'name' => '2026', 'is_current' => 1]);
    $termId = $db->insert('terms', ['school_id' => $schoolId, 'academic_year_id' => $yearId, 'name' => 'Term 1']);
    $db->insert('terms', ['school_id' => $schoolId, 'academic_year_id' => $yearId, 'name' => 'Term 2']);
    $class1 = $db->insert('classes', ['school_id' => $schoolId, 'name' => 'Primary 5']);
    $class2 = $db->insert('classes', ['school_id' => $schoolId, 'name' => 'Primary 6']);

    // ---- Fee items ----
    $tuition   = $db->insert('fee_items', ['school_id' => $schoolId, 'code' => 'TUIT', 'name' => 'Tuition', 'category' => 'tuition']);
    $meals     = $db->insert('fee_items', ['school_id' => $schoolId, 'code' => 'MEAL', 'name' => 'Meals', 'category' => 'meals']);
    $transport = $db->insert('fee_items', ['school_id' => $schoolId, 'code' => 'TRAN', 'name' => 'Transport', 'category' => 'transport']);

    return ['db' => $db, 'schoolId' => $schoolId, 'termId' => $termId, 'class1' => $class1, 'class2' => $class2,
            'tuition' => $tuition, 'meals' => $meals, 'transport' => $transport, 'bursarId' => $bursarId,
            'adminId' => $adminId, 'parentId' => $parentId];
}

function seed_rest(array $ctx): bool
{
    extract($ctx); // db, schoolId, termId, class1, class2, tuition, meals, transport, bursarId, adminId, parentId
    $db = Database::get();

    // Idempotency: skip if already seeded
    if ((int)$db->scalar("SELECT COUNT(*) FROM students") > 0) {
        echo "seed_rest: already done (students exist)\n";
        return true;
    }

    // ---- Fee structure (published) ----
    $structId = $db->insert('fee_structures', [
        'school_id' => $schoolId, 'term_id' => $termId, 'name' => '2026 Term 1 Fees', 'status' => 'published', 'created_by' => $bursarId,
    ]);
    $db->insert('fee_structure_items', ['fee_structure_id' => $structId, 'fee_item_id' => $tuition,   'amount' => 350000]);
    $db->insert('fee_structure_items', ['fee_structure_id' => $structId, 'fee_item_id' => $meals,     'amount' => 150000]);
    $db->insert('fee_structure_items', ['fee_structure_id' => $structId, 'fee_item_id' => $transport, 'amount' => 50000]);

    // ---- Students + obligations ----
    // The parent user links to students through a real guardians row
    // (student_guardians.guardian_id references guardians.id, not users.id).
    $parentGuardianId = null;
    if ($parentId) {
        $row = $db->fetch("SELECT id FROM guardians WHERE email = 'parent@kfs.test'");
        $parentGuardianId = $row ? (int)$row['id'] : (int)$db->insert('guardians', [
            'school_id' => $schoolId, 'name' => 'Mary Sebbi', 'phone' => '256700000000', 'email' => 'parent@kfs.test',
        ]);
    }
    $mkStudent = function (string $name, string $reg, int $classId, int $seq) use ($db, $schoolId, $termId, $structId, $parentGuardianId, $tuition, $meals, $transport) {
        $studentIdValue = StudentIdService::generate('KFS', $seq);
        $sid = $db->insert('students', [
            'school_id' => $schoolId, 'student_id' => $studentIdValue, 'reg_form_number' => $reg,
            'full_name' => $name, 'class_id' => $classId, 'status' => 'active',
        ]);
        $gid = $db->insert('guardians', ['school_id' => $schoolId, 'name' => 'Parent of ' . $name, 'phone' => '2567' . random_int(10000000, 99999999), 'email' => 'g' . $sid . '@example.com']);
        $db->insert('student_guardians', ['student_id' => $sid, 'guardian_id' => $gid, 'relationship' => 'parent']);
        if ($parentGuardianId) {
            $db->insert('student_guardians', ['student_id' => $sid, 'guardian_id' => $parentGuardianId, 'relationship' => 'guardian']);
        }
        $total = 350000 + 150000 + 50000;
        $invoice = 'INV-2026-T1-' . str_pad((string)$sid, 4, '0', STR_PAD_LEFT);
        $obId = $db->insert('fee_obligations', [
            'school_id' => $schoolId, 'student_id' => $sid, 'term_id' => $termId, 'invoice_no' => $invoice,
            'total_amount' => $total, 'status' => 'unpaid',
        ]);
        foreach ([[$tuition, 350000], [$meals, 150000], [$transport, 50000]] as [$item, $amt]) {
            $db->insert('fee_obligation_items', ['fee_obligation_id' => $obId, 'fee_item_id' => $item, 'amount' => $amt]);
        }
        return $sid;
    };

    $mkStudent('John Maswaswa', 'S3-2026-238', $class1, 238);
    $mkStudent('Aisha Nabirye', 'S3-2026-239', $class1, 239);
    $mkStudent('Daniel Kato',   'S3-2026-240', $class2, 240);

    AuditService::log($schoolId, $adminId, 'system.seeded', 'schools', $schoolId, ['message' => 'Demo data installed']);
    return true;
}

// ---- Main execution ----
run_schema();
if (php_sapi_name() === 'cli') {
    $ctx = seed_if_empty();
    if ($ctx === false) {
        echo "Database already seeded.\n";
    } elseif (is_array($ctx)) {
        seed_rest($ctx);
        echo "Schema + demo data installed.\n";
        echo "Login credentials: password123 for all seeded users\n";
        echo "e.g. bursar@kfs.test, cashier@kfs.test, parent@kfs.test, platform@schoolfees.test\n";
    }
} else {
    // Invoked from web (/install) - handled by InstallController instead.
}

