<?php

class RetentionManager
{
    private $hostname;
    private $config;
    private $dataPath;

    public function __construct($hostname)
    {
        $this->hostname = preg_replace("/[^a-zA-Z0-9\.\-_]+/", "", $hostname);
        $this->config = new FolderConfig($this->hostname);
        $this->dataPath = ROOT.DS.'..'.DS.'data'.DS.$this->hostname.DS;
    }

    /**
     * Apply retention rules and delete old backups
     * Returns array of deleted files
     */
    public function applyRetention()
    {
        $retention = $this->config->getRetention();
        
        // If keep_all is enabled, don't delete anything
        if ($retention['keep_all']) {
            return [];
        }

        // Get all files with their metadata
        $files = $this->getFilesWithMetadata();
        
        // Determine which files to keep
        $filesToKeep = $this->determineFilesToKeep($files, $retention);
        
        // Delete files not in the keep list
        $deleted = [];
        foreach ($files as $file) {
            if (!in_array($file['name'], $filesToKeep)) {
                $filepath = $this->dataPath . $file['name'];
                if (unlink($filepath)) {
                    storageControllerDelete($this->hostname, $file['name']);
                    $this->config->removeFile($file['name']);
                    $deleted[] = $file['name'];
                }
            }
        }
        
        $this->config->save();
        return $deleted;
    }

    /**
     * Get all backup files with their metadata
     */
    private function getFilesWithMetadata()
    {
        $files = [];
        $configFiles = $this->config->getFiles();
        
        if (!is_dir($this->dataPath)) {
            return $files;
        }

        $scannedFiles = array_diff(scandir($this->dataPath, SCANDIR_SORT_DESCENDING), array('..', '.', 'config.json'));
        
        foreach ($scannedFiles as $filename) {
            $filepath = $this->dataPath . $filename;
            
            // Skip if it's not a file
            if (!is_file($filepath)) {
                continue;
            }

            // Get timestamp from config or file modification time
            if (isset($configFiles[$filename])) {
                $timestamp = $configFiles[$filename]['uploaded'];
            } else {
                // Fallback: try to parse from filename or use mtime
                $timestamp = $this->extractTimestampFromFilename($filename);
                if (!$timestamp) {
                    $timestamp = filemtime($filepath);
                }
                
                // Add to config for future reference
                $this->config->addFile($filename, filesize($filepath));
            }

            $files[] = [
                'name' => $filename,
                'timestamp' => $timestamp,
                'size' => filesize($filepath),
                'hash' => sha1_file($filepath)
            ];
        }

        // Sort by timestamp descending (newest first)
        usort($files, function($a, $b) {
            return $b['timestamp'] - $a['timestamp'];
        });

        return $files;
    }

    /**
     * Extract timestamp from filename (format: YYYY-MM-DD_HH.MM)
     */
    private function extractTimestampFromFilename($filename)
    {
        if (preg_match('/^(\d{4}-\d{2}-\d{2})[_\s](\d{2})\.(\d{2})/', $filename, $matches)) {
            return strtotime($matches[1] . ' ' . $matches[2] . ':' . $matches[3] . ':00');
        }
        return null;
    }

    /**
     * Determine which files to keep based on retention rules
     * Implements Proxmox-style retention logic
     */
    private function determineFilesToKeep($files, $retention)
    {
        $keep = [];
        $seenHashes = [];

        // Remove duplicates first (same content)
        $uniqueFiles = [];
        foreach ($files as $file) {
            if (!in_array($file['hash'], $seenHashes)) {
                $uniqueFiles[] = $file;
                $seenHashes[] = $file['hash'];
            }
        }
        $files = $uniqueFiles;

        // Apply keep-last rule
        if ($retention['keep_last'] > 0) {
            for ($i = 0; $i < min($retention['keep_last'], count($files)); $i++) {
                $keep[] = $files[$i]['name'];
            }
        }

        // Apply hourly retention
        if ($retention['keep_hourly'] > 0) {
            $keep = array_merge($keep, $this->selectByPeriod($files, 'hourly', $retention['keep_hourly']));
        }

        // Apply daily retention
        if ($retention['keep_daily'] > 0) {
            $keep = array_merge($keep, $this->selectByPeriod($files, 'daily', $retention['keep_daily']));
        }

        // Apply weekly retention
        if ($retention['keep_weekly'] > 0) {
            $keep = array_merge($keep, $this->selectByPeriod($files, 'weekly', $retention['keep_weekly']));
        }

        // Apply monthly retention
        if ($retention['keep_monthly'] > 0) {
            $keep = array_merge($keep, $this->selectByPeriod($files, 'monthly', $retention['keep_monthly']));
        }

        // Apply yearly retention
        if ($retention['keep_yearly'] > 0) {
            $keep = array_merge($keep, $this->selectByPeriod($files, 'yearly', $retention['keep_yearly']));
        }

        return array_unique($keep);
    }

    /**
     * Select files to keep for a specific time period
     */
    private function selectByPeriod($files, $period, $count)
    {
        $keep = [];
        $seenPeriods = [];

        foreach ($files as $file) {
            $periodKey = $this->getPeriodKey($file['timestamp'], $period);
            
            if (!in_array($periodKey, $seenPeriods)) {
                $keep[] = $file['name'];
                $seenPeriods[] = $periodKey;
                
                if (count($seenPeriods) >= $count) {
                    break;
                }
            }
        }

        return $keep;
    }

    /**
     * Get period key for grouping backups
     */
    private function getPeriodKey($timestamp, $period)
    {
        switch ($period) {
            case 'hourly':
                return date('Y-m-d H', $timestamp);
            case 'daily':
                return date('Y-m-d', $timestamp);
            case 'weekly':
                return date('Y-W', $timestamp); // ISO-8601 week
            case 'monthly':
                return date('Y-m', $timestamp);
            case 'yearly':
                return date('Y', $timestamp);
            default:
                return date('Y-m-d', $timestamp);
        }
    }

    /**
     * Get retention statistics
     */
    public function getStats()
    {
        $files = $this->getFilesWithMetadata();
        $totalSize = array_sum(array_column($files, 'size'));
        
        return [
            'total_files' => count($files),
            'total_size' => $totalSize,
            'oldest' => count($files) > 0 ? end($files)['timestamp'] : null,
            'newest' => count($files) > 0 ? $files[0]['timestamp'] : null
        ];
    }
}
