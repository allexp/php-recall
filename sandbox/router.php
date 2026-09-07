<?php

declare(strict_types=1);

const MAX_CODE_BYTES = 20_000;
const MAX_OUTPUT_BYTES = 65_536;
const TIMEOUT_SECONDS = 3.0;

header('Content-Type: application/json; charset=utf-8');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'GET' && ($_SERVER['REQUEST_URI'] ?? '') === '/health') {
    echo json_encode(['status' => 'ok']);
    return;
}

$expectedToken = getenv('SANDBOX_TOKEN') ?: '';
$authorization = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
if ($expectedToken === '' || !hash_equals('Bearer '.$expectedToken, $authorization)) {
    respond(401, ['error' => 'Доступ запрещён.']);
}
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || ($_SERVER['REQUEST_URI'] ?? '') !== '/run') {
    respond(404, ['error' => 'Маршрут не найден.']);
}

$body = file_get_contents('php://input');
$payload = is_string($body) ? json_decode($body, true) : null;
$code = is_array($payload) && is_string($payload['code'] ?? null) ? $payload['code'] : '';
if ($code === '' || strlen($code) > MAX_CODE_BYTES) {
    respond(422, ['error' => 'Некорректный размер кода.']);
}

$containerName = 'php-recall-run-'.bin2hex(random_bytes(8));
$image = getenv('SANDBOX_IMAGE') ?: 'php-recall-sandbox:local';
$disabledFunctions = implode(',', [
    'exec', 'passthru', 'shell_exec', 'system', 'proc_open', 'popen',
    'pcntl_exec', 'putenv', 'mail', 'dl', 'link', 'symlink',
]);
$command = [
    'docker', 'run', '--rm', '--interactive', '--name', $containerName,
    '--network', 'none', '--read-only', '--cap-drop', 'ALL',
    '--security-opt', 'no-new-privileges', '--pids-limit', '32',
    '--memory', '64m', '--memory-swap', '64m', '--cpus', '0.5',
    '--user', '65534:65534', '--tmpfs', '/tmp:rw,noexec,nosuid,size=16m',
    $image, 'php', '-n', '-d', 'display_errors=stderr', '-d', 'log_errors=0',
    '-d', 'memory_limit=32M', '-d', 'max_execution_time=2',
    '-d', 'allow_url_fopen=0', '-d', 'open_basedir=/tmp',
    '-d', 'disable_functions='.$disabledFunctions,
];

$pipes = [];
$process = proc_open($command, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
if (!is_resource($process)) {
    respond(503, ['error' => 'Не удалось создать контейнер.']);
}

fwrite($pipes[0], $code);
fclose($pipes[0]);
stream_set_blocking($pipes[1], false);
stream_set_blocking($pipes[2], false);
$startedAt = microtime(true);
$output = '';
$timedOut = false;
$exitCode = null;

while (true) {
    $output .= stream_get_contents($pipes[1]).stream_get_contents($pipes[2]);
    $status = proc_get_status($process);
    if (!$status['running']) {
        $exitCode = $status['exitcode'];
        break;
    }
    if (microtime(true) - $startedAt > TIMEOUT_SECONDS || strlen($output) > MAX_OUTPUT_BYTES) {
        $timedOut = microtime(true) - $startedAt > TIMEOUT_SECONDS;
        exec('docker kill '.escapeshellarg($containerName).' 2>/dev/null');
        proc_terminate($process, 9);
        break;
    }
    usleep(20_000);
}

$output .= stream_get_contents($pipes[1]).stream_get_contents($pipes[2]);
fclose($pipes[1]);
fclose($pipes[2]);
$closedExitCode = proc_close($process);
$exitCode ??= $closedExitCode;
if (strlen($output) > MAX_OUTPUT_BYTES) {
    $output = substr($output, 0, MAX_OUTPUT_BYTES)."\n… вывод обрезан";
}
if ($timedOut) {
    $output .= ($output === '' ? '' : "\n").'Превышено время выполнения.';
}

respond(200, ['output' => $output, 'exit_code' => $exitCode, 'timed_out' => $timedOut]);

/** @param array<string, mixed> $payload */
function respond(int $status, array $payload): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}
