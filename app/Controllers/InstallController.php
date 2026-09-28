<?php
/** InstallController - runs the schema + demo seed from the browser. */
final class InstallController
{
    public function index(): void
    {
        $installed = (bool)Database::get()->scalar("SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name='users'");
        $userCount = $installed ? (int)Database::get()->scalar('SELECT COUNT(*) FROM users') : 0;
        render('install/run', [
            'title'     => 'Install',
            'installed' => $installed,
            'userCount' => $userCount,
        ]);
    }

    public function run(): void
    {
        csrf_check();

        // Reuse the CLI seeder: defines run_schema(), seed_if_empty(), seed_rest().
        require_once BASE_PATH . '/database/seed.php';

        run_schema();

        $ctx = seed_if_empty();   // false when already seeded, else base context
        if (is_array($ctx)) {
            seed_rest($ctx);
            flash_set('success', 'Installation complete — schema and demo data created. You can now log in.');
        } else {
            flash_set('info', 'Database was already installed. Schema re-checked; no data was duplicated.');
        }

        redirect('/login');
    }
}
