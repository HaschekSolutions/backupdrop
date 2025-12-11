#!/usr/bin/env php
<?php

/**
 * Backupdrop Cron Script
 * 
 * This script should be run periodically (e.g., via cron) to apply retention policies
 * to all backup targets and clean up old backups.
 * 
 * Usage: php cron.php
 * 
 * Example crontab entry (run daily at 3 AM):
 * 0 3 * * * /usr/bin/php /path/to/backupdrop/web/cron.php
 */

// Basic path definitions
define('DS', DIRECTORY_SEPARATOR);
define('ROOT', __DIR__);

// Set timezone to UTC
date_default_timezone_set('UTC');

// Includes
if(file_exists(ROOT.DS.'..'.DS.'config'.DS.'config.inc.php'))
    require_once(ROOT.DS.'..'.DS.'config'.DS.'config.inc.php');
require_once(ROOT.DS.'lib'.DS.'core.php');
require_once(ROOT.DS.'lib'.DS.'helpers.php');
require_once(ROOT.DS.'lib'.DS.'storagecontroller.interface.php');
require_once(ROOT.DS.'lib'.DS.'folderconfig.php');
require_once(ROOT.DS.'lib'.DS.'retention.php');

// Main execution
echo "===========================================\n";
echo "Backupdrop Retention Cleanup\n";
echo "Started: " . date('Y-m-d H:i:s') . " UTC\n";
echo "===========================================\n\n";

$dataDir = ROOT.DS.'..'.DS.'data';
$totalDeleted = 0;
$totalProcessed = 0;

if (!is_dir($dataDir)) {
    echo "ERROR: Data directory not found: $dataDir\n";
    exit(1);
}

// Get all backup target directories
$targets = array_diff(scandir($dataDir), array('..', '.'));

foreach ($targets as $hostname) {
    $targetPath = $dataDir . DS . $hostname;
    
    // Skip if not a directory
    if (!is_dir($targetPath)) {
        continue;
    }
    
    echo "Processing target: $hostname\n";
    $totalProcessed++;
    
    try {
        $retentionManager = new RetentionManager($hostname);
        
        // Get stats before cleanup
        $statsBefore = $retentionManager->getStats();
        echo "  Files before: {$statsBefore['total_files']}\n";
        echo "  Total size: " . formatBytes($statsBefore['total_size']) . "\n";
        
        // Apply retention
        $deleted = $retentionManager->applyRetention();
        
        if (count($deleted) > 0) {
            echo "  Deleted " . count($deleted) . " file(s):\n";
            foreach ($deleted as $file) {
                echo "    - $file\n";
            }
            $totalDeleted += count($deleted);
        } else {
            echo "  No files deleted (retention rules satisfied)\n";
        }
        
        // Get stats after cleanup
        $statsAfter = $retentionManager->getStats();
        echo "  Files after: {$statsAfter['total_files']}\n";
        echo "  Space freed: " . formatBytes($statsBefore['total_size'] - $statsAfter['total_size']) . "\n";
        
    } catch (Exception $e) {
        echo "  ERROR: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
}

echo "===========================================\n";
echo "Summary:\n";
echo "  Targets processed: $totalProcessed\n";
echo "  Total files deleted: $totalDeleted\n";
echo "Completed: " . date('Y-m-d H:i:s') . " UTC\n";
echo "===========================================\n";

exit(0);
