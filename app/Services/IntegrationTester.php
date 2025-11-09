<?php

namespace App\Services;

use App\Models\Integration;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;

class IntegrationTester
{
    public function test(Integration $integration)
    {
        $config = $integration->encrypted_value ?? [];
        $type = $integration->type;

        return match ($type) {
            'airtable' => $this->testAirtable($config),
            'notion' => $this->testNotion($config),
            'mysql' => $this->testMysql($config),
            'smtp' => $this->testSmtp($config),
            'api' => $this->testApi($config),
            'webhook' => $this->testWebhook($config),
            'oauth2' => $this->testOauth2($config),
            'podio' => $this->testPodio($config),
            'tape' => $this->testTape($config),
            default => throw new Exception('Unknown integration type.'),
        };
    }

    protected function testAirtable($config)
    {        
        $response = Http::withToken($config['pat'])->get('https://api.airtable.com/v0/meta/bases');
        if ($response->successful()) return 'Airtable connected.';
        throw new \Exception(
            'Airtable connection failed. Status: ' . $response->status() .
            ' Body: ' . $response->body()
        );
    }

    protected function testMysql($config)
    {
        try {
            // Define a temporary connection dynamically
            $connectionName = 'temp_mysql_' . uniqid();
    
            config([
                "database.connections.$connectionName" => [
                    'driver'   => 'mysql',
                    'host'     => $config['host'],
                    'port'     => $config['port'] ?? 3306,
                    'database' => $config['database'],
                    'username' => $config['username'],
                    'password' => $config['password'],
                    'charset'  => 'utf8mb4',
                    'collation'=> 'utf8mb4_unicode_ci',
                    'options'  => extension_loaded('pdo_mysql') ? [
                        \PDO::ATTR_TIMEOUT => 5,
                    ] : [],
                ],
            ]);
    
            // Now test the connection
            DB::purge($connectionName); // ensure fresh config load
            DB::connection($connectionName)->getPdo();
    
            // Clean up afterwards
            DB::disconnect($connectionName);
    
            return 'MySQL connected successfully.';
        } catch (\Throwable $e) {
            throw new \Exception('MySQL connection failed: ' . $e->getMessage());
        }
    }
    
    protected function testSmtp($config)
    {
        $host = $config['host'] ?? 'localhost';
        $port = $config['port'] ?? 587;
    
        try {
            $connection = @fsockopen($host, $port, $errno, $errstr, 5);
    
            if (! $connection) {
                throw new \Exception("Could not connect to {$host}:{$port} — {$errstr} ({$errno})");
            }
    
            fclose($connection);
            return "SMTP server reachable at {$host}:{$port}.";
        } catch (\Throwable $e) {
            throw new \Exception('SMTP connection failed: ' . $e->getMessage());
        }
    }
    
    protected function testPodio($config)
    {
        $response = Http::asForm()->post('https://podio.com/oauth/token', [
            'grant_type' => 'refresh_token',
            'client_id' => $config['client_id'],
            'client_secret' => $config['client_secret'],
            'refresh_token' => $config['refresh_token'],
        ]);
        if ($response->successful()) return 'Podio connected.';
        throw new Exception('Podio connection failed.');
    }

    protected function testTape($config)
    {
        $response = Http::withToken($config['api_key'])
            ->get('https://api.tapeapp.com/v1/user');
        if ($response->successful()) return 'Tape connected.';
        throw new Exception('Tape connection failed.');
    }
}
