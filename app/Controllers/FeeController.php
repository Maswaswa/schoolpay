<?php
/** FeeController - fee items, structures, term billing (bursar/school admin). */
final class FeeController
{
    public function items(): void
    {
        Auth::requireLogin();
        Auth::requireCan('fees.manage');
        $db = Database::get();
        $schoolId = (int)Auth::schoolId();
        $items = $db->fetchAll('SELECT * FROM fee_items WHERE school_id = :s ORDER BY category, name', ['s' => $schoolId]);
        render('fees/items', ['title' => 'Fee items', 'items' => $items]);
    }

    public function storeItem(): void
    {
        Auth::requireLogin();
        Auth::requireCan('fees.manage');
        csrf_check();
        $db = Database::get();
        $schoolId = (int)Auth::schoolId();

        $code = strtoupper(trim((string)($_POST['code'] ?? '')));
        $name = trim((string)($_POST['name'] ?? ''));
        $category = trim((string)($_POST['category'] ?? 'tuition')) ?: 'tuition';

        if ($code === '' || $name === '') {
            flash_set('error', 'Code and name are required.');
            redirect('/fees/items');
        }
        $exists = $db->fetch('SELECT id FROM fee_items WHERE school_id = :s AND code = :c', ['s' => $schoolId, 'c' => $code]);
        if ($exists) {
            flash_set('error', "Fee item code '{$code}' already exists.");
            redirect('/fees/items');
        }
        $db->insert('fee_items', ['school_id' => $schoolId, 'code' => $code, 'name' => $name, 'category' => $category]);
        AuditService::log($schoolId, Auth::id(), 'fee_item.created', 'fee_items', null, ['code' => $code, 'name' => $name]);
        flash_set('success', "Fee item '{$name}' created.");
        redirect('/fees/items');
    }

    public function structures(): void
    {
        Auth::requireLogin();
        Auth::requireCan('fees.manage');
        $db = Database::get();
        $schoolId = (int)Auth::schoolId();
        $structures = $db->fetchAll(
            "SELECT fs.*, t.name AS term_name, u.name AS created_by_name,
                    COALESCE((SELECT SUM(fsi.amount) FROM fee_structure_items fsi WHERE fsi.fee_structure_id = fs.id),0) AS total_amount
             FROM fee_structures fs
             JOIN terms t ON t.id = fs.term_id
             LEFT JOIN users u ON u.id = fs.created_by
             WHERE fs.school_id = :s ORDER BY fs.id DESC", ['s' => $schoolId]
        );
        $terms = $db->fetchAll(
            'SELECT t.*, ay.name AS year_name FROM terms t JOIN academic_years ay ON ay.id = t.academic_year_id
             WHERE t.school_id = :s ORDER BY t.id DESC', ['s' => $schoolId]
        );
        $feeItems = $db->fetchAll('SELECT * FROM fee_items WHERE school_id = :s ORDER BY name', ['s' => $schoolId]);
        render('fees/structures', ['title' => 'Fee structures', 'structures' => $structures, 'terms' => $terms, 'feeItems' => $feeItems]);
    }

    public function createStructure(): void
    {
        Auth::requireLogin();
        Auth::requireCan('fees.manage');
        redirect('/fees/structures');
    }

    public function storeStructure(): void
    {
        Auth::requireLogin();
        Auth::requireCan('fees.manage');
        csrf_check();
        $db = Database::get();
        $schoolId = (int)Auth::schoolId();

        $termId = (int)($_POST['term_id'] ?? 0);
        $name   = trim((string)($_POST['name'] ?? ''));
        $itemIds = $_POST['fee_item_id'] ?? [];
        $amounts = $_POST['amount'] ?? [];

        if (!$termId || $name === '' || !is_array($itemIds) || !count($itemIds)) {
            flash_set('error', 'Term, name and at least one fee line are required.');
            redirect('/fees/structures');
        }

        $structureId = $db->insert('fee_structures', [
            'school_id' => $schoolId, 'term_id' => $termId, 'name' => $name,
            'status' => 'published', 'created_by' => Auth::id(),
        ]);
        foreach ($itemIds as $i => $itemId) {
            $amount = (float)($amounts[$i] ?? 0);
            if ($amount <= 0) { continue; }
            $db->insert('fee_structure_items', [
                'fee_structure_id' => $structureId, 'fee_item_id' => (int)$itemId, 'amount' => $amount,
            ]);
        }
        AuditService::log($schoolId, Auth::id(), 'fee_structure.created', 'fee_structures', $structureId, ['name' => $name]);
        flash_set('success', "Fee structure '{$name}' published.");
        redirect('/fees/structures');
    }

    public function billing(): void
    {
        Auth::requireLogin();
        Auth::requireCan('billing.manage');
        $db = Database::get();
        $schoolId = (int)Auth::schoolId();
        $structures = $db->fetchAll(
            "SELECT fs.*, t.name AS term_name,
                    COALESCE((SELECT SUM(fsi.amount) FROM fee_structure_items fsi WHERE fsi.fee_structure_id = fs.id),0) AS total_amount,
                    (SELECT COUNT(*) FROM fee_obligations fo WHERE fo.term_id = fs.term_id) AS invoices_count
             FROM fee_structures fs JOIN terms t ON t.id = fs.term_id
             WHERE fs.school_id = :s ORDER BY fs.id DESC", ['s' => $schoolId]
        );
        render('fees/billing', ['title' => 'Invoice a term', 'structures' => $structures]);
    }

    /** Bulk-invoice every active student from a published fee structure. */
    public function runBilling(): void
    {
        Auth::requireLogin();
        Auth::requireCan('billing.manage');
        csrf_check();
        $db = Database::get();
        $schoolId = (int)Auth::schoolId();
        $structureId = (int)($_POST['fee_structure_id'] ?? 0);

        $struct = $db->fetch(
            "SELECT fs.*, t.name AS term_name FROM fee_structures fs JOIN terms t ON t.id = fs.term_id
             WHERE fs.id = :id AND fs.school_id = :s", ['id' => $structureId, 's' => $schoolId]
        );
        if (!$struct) {
            flash_set('error', 'Fee structure not found.');
            redirect('/fees/billing');
        }

        $termId = (int)$struct['term_id'];
        $items = $db->fetchAll('SELECT fee_item_id, amount FROM fee_structure_items WHERE fee_structure_id = :id', ['id' => $structureId]);
        $total = array_sum(array_column($items, 'amount'));
        $students = $db->fetchAll("SELECT id FROM students WHERE school_id = :s AND status = 'active'", ['s' => $schoolId]);

        $created = 0; $skipped = 0;
        $db->transaction(function ($db) use ($students, $items, $total, $termId, $schoolId, &$created, &$skipped) {
            foreach ($students as $st) {
                $sid = (int)$st['id'];
                $already = $db->fetch(
                    'SELECT id FROM fee_obligations WHERE student_id = :sid AND term_id = :tid',
                    ['sid' => $sid, 'tid' => $termId]
                );
                if ($already) { $skipped++; continue; }

                $invoice = 'INV-' . date('Y') . '-T' . $termId . '-' . str_pad((string)$sid, 4, '0', STR_PAD_LEFT);
                $obId = $db->insert('fee_obligations', [
                    'school_id' => $schoolId, 'student_id' => $sid, 'term_id' => $termId,
                    'invoice_no' => $invoice, 'total_amount' => $total, 'status' => 'unpaid', 'created_by' => Auth::id(),
                ]);
                foreach ($items as $item) {
                    $db->insert('fee_obligation_items', [
                        'fee_obligation_id' => $obId, 'fee_item_id' => (int)$item['fee_item_id'], 'amount' => (float)$item['amount'],
                    ]);
                }
                $created++;
            }
        });

        AuditService::log($schoolId, Auth::id(), 'billing.run', 'fee_obligations', null, [
            'structure' => $struct['name'], 'created' => $created, 'skipped' => $skipped,
        ]);
        flash_set('success', "Billing complete: {$created} invoice(s) created, {$skipped} already billed (skipped).");
        redirect('/fees/billing');
    }
}
