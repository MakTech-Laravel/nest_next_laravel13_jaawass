<?php

namespace App\Services\Database;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseSqlExporter
{
    /** @var list<string>|null */
    private ?array $cachedTables = null;

    /**
     * Active connection database name from config (DB_DATABASE in .env).
     */
    private function connectionDatabaseName(): string
    {
        $connection = (string) config('database.default');
        $database = config("database.connections.{$connection}.database");

        if (! is_string($database) || $database === '') {
            throw new \RuntimeException('Database name is not configured. Set DB_DATABASE in your .env file.');
        }

        return $database;
    }

    /**
     * @return list<string>
     */
    public function listTables(): array
    {
        if ($this->cachedTables !== null) {
            return $this->cachedTables;
        }

        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            $this->cachedTables = collect(DB::select("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%'"))
                ->map(fn (object $row): string => (string) $row->name)
                ->sort()
                ->values()
                ->all();

            return $this->cachedTables;
        }

        if ($driver === 'mysql') {
            $this->cachedTables = $this->listMysqlTables();

            return $this->cachedTables;
        }

        // Schema::getTableListing() may include other databases/schemas (e.g. "other_db.users").
        // Only return tables that belong to the configured application database (DB_DATABASE).
        $database = $this->connectionDatabaseName();

        $this->cachedTables = collect(Schema::getTableListing())
            ->map(function (string $table) use ($database): ?string {
                if (! str_contains($table, '.')) {
                    return $table;
                }

                [$schema, $name] = explode('.', $table, 2);

                return $schema === $database ? $name : null;
            })
            ->filter()
            ->reject(fn (string $table): bool => str_starts_with($table, 'sqlite_'))
            ->sort()
            ->values()
            ->all();

        return $this->cachedTables;
    }

    public function tableExists(string $table): bool
    {
        $table = $this->normalizeTableName($table);

        if ($table === '' || str_contains($table, '.')) {
            return false;
        }

        return Schema::hasTable($table);
    }

    private function normalizeTableName(string $table): string
    {
        $table = trim($table);

        if (! str_contains($table, '.')) {
            return $table;
        }

        [$schema, $name] = explode('.', $table, 2);

        return $schema === $this->connectionDatabaseName() ? $name : $table;
    }

    /**
     * @return list<string>
     */
    private function listMysqlTables(): array
    {
        $database = $this->connectionDatabaseName();
        $escapedDatabase = str_replace('`', '``', $database);
        $column = 'Tables_in_'.$database;

        return collect(DB::select("SHOW TABLES FROM `{$escapedDatabase}`"))
            ->map(function (object $row) use ($column): string {
                if (isset($row->{$column})) {
                    return (string) $row->{$column};
                }

                return (string) array_values((array) $row)[0];
            })
            ->sort()
            ->values()
            ->all();
    }

    public function countRows(string $table): int
    {
        $table = $this->assertValidTable($table);

        return (int) DB::table($table)->count();
    }

    public function exportTableStructure(string $table): string
    {
        $table = $this->assertValidTable($table);

        $driver = DB::connection()->getDriverName();
        $quotedTable = $this->quoteIdentifier($table);

        if ($driver === 'mysql') {
            $result = DB::select("SHOW CREATE TABLE {$quotedTable}");
            $createStatement = $result[0]->{'Create Table'} ?? '';

            return "DROP TABLE IF EXISTS {$quotedTable};\n\n{$createStatement};\n\n";
        }

        if ($driver === 'sqlite') {
            $result = DB::select("SELECT sql FROM sqlite_master WHERE type = 'table' AND name = ?", [$table]);
            $createStatement = $result[0]->sql ?? '';

            return "DROP TABLE IF EXISTS {$quotedTable};\n\n{$createStatement};\n\n";
        }

        return "-- Structure export is not supported for driver: {$driver}\n\n";
    }

    public function exportTableDataChunk(string $table, int $offset, int $limit): string
    {
        $table = $this->assertValidTable($table);

        $rows = DB::table($table)->offset($offset)->limit($limit)->get();

        if ($rows->isEmpty()) {
            return '';
        }

        $quotedTable = $this->quoteIdentifier($table);
        $columns = array_keys((array) $rows->first());
        $quotedColumns = array_map(fn (string $column): string => $this->quoteIdentifier($column), $columns);
        $columnList = implode(', ', $quotedColumns);
        $sql = '';

        foreach ($rows as $row) {
            $values = collect($columns)
                ->map(fn (string $column): string => $this->quoteValue($row->{$column} ?? null))
                ->implode(', ');

            $sql .= "INSERT INTO {$quotedTable} ({$columnList}) VALUES ({$values});\n";
        }

        return $sql;
    }

    public function quoteIdentifier(string $identifier): string
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql') {
            return '`'.str_replace('`', '``', $identifier).'`';
        }

        return '"'.str_replace('"', '""', $identifier).'"';
    }

    private function quoteValue(mixed $value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        if ($value instanceof \DateTimeInterface) {
            return DB::getPdo()->quote($value->format('Y-m-d H:i:s'));
        }

        return DB::getPdo()->quote((string) $value);
    }

    /**
     * @return string Normalized table name for the active connection database.
     */
    public function assertValidTable(string $table): string
    {
        $table = $this->normalizeTableName($table);

        if (! $this->tableExists($table)) {
            throw new \InvalidArgumentException("Table [{$table}] is not allowed for export.");
        }

        return $table;
    }
}
