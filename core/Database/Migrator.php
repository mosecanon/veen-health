<?php
namespace Veen\Core\Database;

/**
 * Migration files live in database/migrations and return:
 *   ['up' => ['SQL', ...], 'down' => ['SQL', ...]]
 */
class Migrator
{
    private string $dir;

    public function __construct(private ?\Closure $out = null)
    {
        $this->dir = base_path('database/migrations');
        $this->out = $out ?? fn ($m) => print($m . PHP_EOL);
    }

    private function say(string $m): void { ($this->out)($m); }

    private function ensureTable(): void
    {
        DB::unprepared('CREATE TABLE IF NOT EXISTS migrations (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            migration VARCHAR(191) NOT NULL UNIQUE,
            batch INT NOT NULL,
            ran_at DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    private function files(): array
    {
        $files = glob($this->dir . '/*.php') ?: [];
        sort($files);
        return $files;
    }

    public function migrate(): void
    {
        $this->ensureTable();
        $ran = array_column(DB::select('SELECT migration FROM migrations'), 'migration');
        $batch = (int) (DB::selectOne('SELECT MAX(batch) AS b FROM migrations')['b'] ?? 0) + 1;
        $count = 0;
        foreach ($this->files() as $file) {
            $name = basename($file, '.php');
            if (in_array($name, $ran, true)) continue;
            $def = require $file;
            foreach ((array) $def['up'] as $sql) {
                DB::unprepared($sql);
            }
            DB::statement('INSERT INTO migrations (migration, batch, ran_at) VALUES (?, ?, NOW())', [$name, $batch]);
            $this->say("  ✔ Migrated: $name");
            $count++;
        }
        $this->say($count ? "Done. $count migration(s) ran." : 'Nothing to migrate.');
    }

    public function fresh(): void
    {
        $db = config('database.database');
        DB::unprepared('SET FOREIGN_KEY_CHECKS = 0');
        foreach (DB::select('SELECT table_name AS t FROM information_schema.tables WHERE table_schema = ?', [$db]) as $row) {
            DB::unprepared('DROP TABLE IF EXISTS `' . str_replace('`', '', $row['t']) . '`');
        }
        DB::unprepared('SET FOREIGN_KEY_CHECKS = 1');
        $this->say('Dropped all tables.');
        $this->migrate();
    }
}
