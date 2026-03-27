<?php

namespace App\Services\Sap;

use RuntimeException;

class HanaOdbcConnector
{
    /**
     * Resource koneksi ODBC (bukan class).
     *
     * @var resource|null
     */
    protected $connection = null;

    public function __construct(
        protected string $hanaUrl,
        protected string $odbcDriver
    ) {
    }

    /**
     * Parse HANA_DB_URL: hdb://user:pass@host:port/DB
     */
    protected function parseUrl(): array
    {
        $parts = parse_url($this->hanaUrl);

        if (! $parts || ! isset($parts['host'], $parts['user'], $parts['pass'])) {
            throw new RuntimeException('HANA_DB_URL tidak valid.');
        }

        return [
            'user'     => $parts['user'],
            'password' => $parts['pass'],
            'host'     => $parts['host'],
            'port'     => $parts['port'] ?? 30015,
            'database' => isset($parts['path']) ? ltrim($parts['path'], '/') : null,
        ];
    }

    /**
     * Lazy connect ke HANA via ODBC.
     *
     * @return void
     */
    protected function connect(): void
    {
        if (is_resource($this->connection)) {
            return;
        }

        $config = $this->parseUrl();

        // Connection string HANA ODBC
        $connString = sprintf(
            'DRIVER=%s;SERVERNODE=%s:%d;%s',
            $this->odbcDriver,
            $config['host'],
            $config['port'],
            $config['database'] ? 'DATABASE=' . $config['database'] . ';' : ''
        );

        $connection = @odbc_connect($connString, $config['user'], $config['password']);

        if (! $connection) {
            $error = odbc_errormsg();
            throw new RuntimeException('Gagal konek HANA ODBC: ' . $error);
        }

        $this->connection = $connection;
    }

    /**
     * Jalankan SELECT, kembalikan array asosiatif.
     *
     * @param  string  $sql
     * @param  array   $bindings
     * @return array
     */
    public function select(string $sql, array $bindings = []): array
    {
        $this->connect();

        if (! is_resource($this->connection)) {
            throw new RuntimeException('Koneksi ODBC tidak tersedia.');
        }

        if ($bindings) {
            $stmt = @odbc_prepare($this->connection, $sql);

            if (! $stmt) {
                throw new RuntimeException('Gagal prepare statement: ' . odbc_errormsg($this->connection));
            }

            $exec = @odbc_execute($stmt, array_values($bindings));

            if (! $exec) {
                throw new RuntimeException('Gagal eksekusi statement: ' . odbc_errormsg($this->connection));
            }

            $result = $stmt;
        } else {
            $result = @odbc_exec($this->connection, $sql);

            if (! $result) {
                throw new RuntimeException('Gagal eksekusi query: ' . odbc_errormsg($this->connection));
            }
        }

        $rows = [];

        while ($row = odbc_fetch_array($result)) {
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * Menutup koneksi bila perlu.
     *
     * @return void
     */
    public function disconnect(): void
    {
        if (is_resource($this->connection)) {
            odbc_close($this->connection);
        }

        $this->connection = null;
    }
}
