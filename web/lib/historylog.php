<?php

class HistoryLog
{
    private string $logPath;

    public function __construct(string $hostname)
    {
        $hostname = preg_replace("/[^a-zA-Z0-9\.\-_]+/", "", $hostname);
        $dir = ROOT.DS.'..'.DS.'data'.DS.$hostname;
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $this->logPath = $dir.DS.'history.log';
    }

    /**
     * Append a structured event to the log.
     * Each line is a JSON object (JSON Lines format).
     */
    public function append(string $event, array $data = []): void
    {
        $entry = array_merge([
            'ts'    => date('c'),           // ISO 8601 timestamp
            'event' => $event,
        ], $data);

        file_put_contents(
            $this->logPath,
            json_encode($entry, JSON_UNESCAPED_SLASHES)."\n",
            FILE_APPEND | LOCK_EX
        );
    }

    /**
     * Read the last $limit entries from the log, newest first.
     * Returns an array of decoded objects.
     */
    public function read(int $limit = 100): array
    {
        if (!file_exists($this->logPath)) {
            return [];
        }
        $lines = file($this->logPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!$lines) return [];
        $lines = array_reverse($lines);
        $entries = [];
        foreach (array_slice($lines, 0, $limit) as $line) {
            $decoded = json_decode($line, true);
            if ($decoded) $entries[] = $decoded;
        }
        return $entries;
    }

    public function getPath(): string
    {
        return $this->logPath;
    }
}