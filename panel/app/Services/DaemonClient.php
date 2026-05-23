<?php

namespace App\Services;

use App\Models\Node;
use App\Models\Server;
use GuzzleHttp\Client;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Psr\Http\Message\ResponseInterface;

class DaemonClient
{
    public function request(Node $node, string $method, string $uri, array $payload = []): array
    {
        $client = new Client([
            'base_uri' => rtrim($node->daemon_url, '/').'/',
            'timeout' => 45,
            'http_errors' => false,
        ]);

        $options = [
            'headers' => [
                'Authorization' => 'Bearer '.$node->token,
                'Accept' => 'application/json',
            ],
        ];

        if ($payload !== []) {
            $options['json'] = $payload;
        }

        $response = $client->request($method, ltrim($uri, '/'), $options);
        $body = json_decode((string) $response->getBody(), true);

        if ($response->getStatusCode() >= 400) {
            throw new \RuntimeException($body['error'] ?? 'Daemon request failed with HTTP '.$response->getStatusCode());
        }

        return is_array($body) ? $body : [];
    }

    public function createServer(Server $server): array
    {
        $allocation = $server->allocation;

        return $this->request($server->node, 'POST', '/servers/create', [
            'uuid' => $server->uuid,
            'name' => $server->name,
            'image' => $server->docker_image,
            'startup' => $server->startup_command,
            'env' => $server->environment ?? [],
            'memory_mb' => $server->memory_mb,
            'cpu_limit' => $server->cpu_limit,
            'disk_mb' => $server->disk_mb,
            'ports' => $allocation ? [
                ['ip' => $allocation->ip, 'port' => $allocation->port, 'primary' => true],
            ] : [],
        ]);
    }

    public function power(Server $server, string $action): array
    {
        return $this->request($server->node, 'POST', "/servers/{$server->uuid}/{$action}");
    }

    public function stats(Server $server): array
    {
        return $this->request($server->node, 'GET', "/servers/{$server->uuid}/stats");
    }

    public function files(Server $server, string $path = '/'): array
    {
        return $this->request($server->node, 'GET', "/servers/{$server->uuid}/files?path=".rawurlencode($path));
    }

    public function writeFile(Server $server, string $path, string $content): array
    {
        return $this->request($server->node, 'POST', "/servers/{$server->uuid}/files/write", [
            'path' => $path,
            'content' => $content,
        ]);
    }

    public function deleteFile(Server $server, string $path): array
    {
        return $this->request($server->node, 'DELETE', "/servers/{$server->uuid}/files/delete", [
            'path' => $path,
        ]);
    }

    public function makeDirectory(Server $server, string $path): array
    {
        return $this->request($server->node, 'POST', "/servers/{$server->uuid}/files/mkdir", [
            'path' => $path,
        ]);
    }

    public function rename(Server $server, string $from, string $to): array
    {
        return $this->request($server->node, 'PATCH', "/servers/{$server->uuid}/files/rename", [
            'from' => $from,
            'to' => $to,
        ]);
    }

    public function upload(Server $server, string $path, UploadedFile $file): array
    {
        $client = new Client([
            'base_uri' => rtrim($server->node->daemon_url, '/').'/',
            'timeout' => 120,
            'http_errors' => false,
        ]);

        $response = $client->post("servers/{$server->uuid}/files/upload", [
            'headers' => [
                'Authorization' => 'Bearer '.$server->node->token,
                'Accept' => 'application/json',
            ],
            'multipart' => [
                ['name' => 'path', 'contents' => $path],
                ['name' => 'file', 'contents' => fopen($file->getRealPath(), 'rb'), 'filename' => $file->getClientOriginalName()],
            ],
        ]);

        $body = json_decode((string) $response->getBody(), true);
        if ($response->getStatusCode() >= 400) {
            throw new \RuntimeException(Arr::get($body, 'error', 'Upload failed.'));
        }

        return is_array($body) ? $body : [];
    }

    public function backup(Server $server, string $name): array
    {
        return $this->request($server->node, 'POST', "/servers/{$server->uuid}/backup", [
            'name' => $name,
        ]);
    }

    public function restore(Server $server, string $backup): array
    {
        return $this->request($server->node, 'POST', "/servers/{$server->uuid}/restore", [
            'backup' => $backup,
        ]);
    }

    public function deleteBackup(Server $server, string $backup): array
    {
        return $this->request($server->node, 'DELETE', "/servers/{$server->uuid}/backup", [
            'backup' => $backup,
        ]);
    }

    public function downloadBackup(Server $server, string $backup): ResponseInterface
    {
        $client = new Client([
            'base_uri' => rtrim($server->node->daemon_url, '/').'/',
            'timeout' => 300,
            'http_errors' => false,
        ]);

        $response = $client->get("servers/{$server->uuid}/backup", [
            'headers' => [
                'Authorization' => 'Bearer '.$server->node->token,
            ],
            'query' => ['backup' => basename($backup)],
        ]);

        if ($response->getStatusCode() >= 400) {
            throw new \RuntimeException('Backup download failed with HTTP '.$response->getStatusCode());
        }

        return $response;
    }
}
