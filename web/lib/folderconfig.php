<?php

class FolderConfig
{
    private $hostname;
    private $configPath;
    private $config;

    public function __construct($hostname)
    {
        $this->hostname = preg_replace("/[^a-zA-Z0-9\.\-_]+/", "", $hostname);
        $this->configPath = ROOT.DS.'..'.DS.'data'.DS.$this->hostname.DS.'config.json';
        $this->load();
    }

    private function load()
    {
        if (file_exists($this->configPath)) {
            $json = file_get_contents($this->configPath);
            $this->config = json_decode($json, true);
            if (!$this->config) {
                $this->config = $this->getDefaultConfig();
            }
        } else {
            $this->config = $this->getDefaultConfig();
            // Index existing files in the directory
            $this->indexExistingFiles();
            // Save the config with indexed files
            $this->save();
        }
    }

    private function indexExistingFiles()
    {
        $dir = ROOT.DS.'..'.DS.'data'.DS.$this->hostname;
        if (is_dir($dir)) {
            $files = scandir($dir);
            foreach ($files as $file) {
                if ($file === '.' || $file === '..' || $file === 'config.json') {
                    continue;
                }
                $filepath = $dir.DS.$file;
                if (is_file($filepath)) {
                    $this->config['files'][$file] = [
                        'uploaded' => filemtime($filepath),
                        'size' => filesize($filepath)
                    ];
                }
            }
        }
    }

    private function getDefaultConfig()
    {
        return [
            'password' => '',
            'retention' => [
                'keep_all' => true,
                'keep_last' => 0,
                'keep_hourly' => 0,
                'keep_daily' => 0,
                'keep_weekly' => 0,
                'keep_monthly' => 0,
                'keep_yearly' => 0
            ],
            'files' => [],
            'ip_allowlist' => []
        ];
    }

    public function save()
    {
        $dir = dirname($this->configPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        return file_put_contents($this->configPath, json_encode($this->config, JSON_PRETTY_PRINT));
    }

    public function get($key = null)
    {
        if ($key === null) {
            return $this->config;
        }
        return $this->config[$key] ?? null;
    }

    public function set($key, $value)
    {
        $this->config[$key] = $value;
    }

    public function setRetention($retention)
    {
        $this->config['retention'] = array_merge($this->config['retention'], $retention);
    }

    public function setPassword($password)
    {
        if ($password) {
            $this->config['password'] = password_hash($password, PASSWORD_DEFAULT);
        } else {
            $this->config['password'] = '';
        }
    }

    public function verifyPassword($password)
    {
        if (!$this->config['password']) {
            return true; // No password set
        }
        return password_verify($password, $this->config['password']);
    }

    public function hasPassword()
    {
        return !empty($this->config['password']);
    }

    public function addFile($filename, $size)
    {
        $this->config['files'][$filename] = [
            'uploaded' => time(),
            'size' => $size
        ];
    }

    public function removeFile($filename)
    {
        if (isset($this->config['files'][$filename])) {
            unset($this->config['files'][$filename]);
        }
    }

    public function getFiles()
    {
        return $this->config['files'] ?? [];
    }

    public function getRetention()
    {
        return $this->config['retention'];
    }

    public function getIpAllowlist(): array
    {
        return $this->config['ip_allowlist'] ?? [];
    }

    public function setIpAllowlist(array $ips)
    {
        $valid = [];
        foreach ($ips as $ip) {
            if (!is_string($ip)) {
                continue;
            }
            $ip = trim($ip);
            if ($ip === '' || !$this->isValidIpEntry($ip)) {
                continue;
            }
            $valid[] = $ip;
        }
        $this->config['ip_allowlist'] = $valid;
        $this->save();
    }

    public function isIpAllowed(string $ip): bool
    {
        $allowlist = $this->getIpAllowlist();
        if (empty($allowlist)) {
            return true; // No allowlist configured = allow all
        }
        foreach ($allowlist as $entry) {
            if ($this->ipInCidr($ip, $entry)) {
                return true;
            }
        }
        return false;
    }

    private function isValidIpEntry(string $entry): bool
    {
        // Exact IP (IPv4 or IPv6)
        if (filter_var($entry, FILTER_VALIDATE_IP)) {
            return true;
        }
        // CIDR range
        if (str_contains($entry, '/')) {
            [$subnet, $bits] = explode('/', $entry, 2);
            if (!ctype_digit($bits)) {
                return false;
            }
            $bits = (int)$bits;
            if (filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                return $bits >= 0 && $bits <= 32;
            }
            if (filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                return $bits >= 0 && $bits <= 128;
            }
        }
        return false;
    }

    private function ipInCidr(string $ip, string $cidr): bool
    {
        if (!str_contains($cidr, '/')) {
            return $ip === $cidr;
        }
        [$subnet, $bits] = explode('/', $cidr, 2);
        $bits = (int)$bits;
        // Handle IPv4
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $ipLong = ip2long($ip);
            $subnetLong = ip2long($subnet);
            $mask = $bits === 0 ? 0 : (~0 << (32 - $bits));
            return ($ipLong & $mask) === ($subnetLong & $mask);
        }
        // Handle IPv6
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) && filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $ipBin = inet_pton($ip);
            $subnetBin = inet_pton($subnet);
            $byteMask = str_repeat("\xff", intdiv($bits, 8));
            $remaining = $bits % 8;
            if ($remaining > 0) $byteMask .= chr(0xff & (0xff << (8 - $remaining)));
            $byteMask = str_pad($byteMask, strlen($ipBin), "\x00");
            return ($ipBin & $byteMask) === ($subnetBin & $byteMask);
        }
        return false;
    }

    public function isDirectoryWritable()
    {
        $dir = dirname($this->configPath);
        if (!is_dir($dir)) {
            return is_writable(dirname($dir));
        }
        return is_writable($dir);
    }

    public function getDirectoryPath()
    {
        return dirname($this->configPath);
    }
}
