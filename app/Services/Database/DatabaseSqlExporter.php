<?php

namespace App\Services\Database;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseSqlExporter
{
    /**
     * @return list<string>
     */
    public function listTables(): array
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            return collect(DB::select("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%'"))
                ->map(fn (object $row): string => (string) $row->name)
                ->sort()
                ->values()
                ->all();
        }

        return collect(Schema::getTableListing())
            ->reject(fn (string $table): bool => str_starts_with($table, 'sqlite_'))
            ->sort()
            ->values()
            ->all();
    }

    public function countRows(string $table): int
    {
        $this->assertValidTable($table);

        return (int) DB::table($table)->count();
    }

    public function exportTableStructure(string $table): string
    {
        $this->assertValidTable($table);

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
        $this->assertValidTable($table);

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

    public function assertValidTable(string $table): void
    {
        if (! in_array($table, $this->listTables(), true)) {
            throw new \InvalidArgumentException("Table [{$table}] is not allowed for export.");
        }
    }
}
