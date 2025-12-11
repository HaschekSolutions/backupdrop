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
            'files' => []
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
}
