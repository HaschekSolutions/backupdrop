<?php

// copy this file to config.inc.php
// and edit to your needs


// AGE encryption settings
// More info on age encryption: https://github.com/FiloSottile/age
define('ENCRYPTION_AGE_SSH_PUBKEY',''); // Enter your SSH public key here to automatically encrypt all uploads
define('ENCRYPTION_AGE_PUBKEY',''); // Enter an "age public key" created with `age-keygen -o key.txt` here to automatically encrypt all uploads with this key

// global settings for retention and version control
// 0 means unlimited
define('KEEP_N_BACKUPS',0);     // How many uploads will be saved. Oldest one will be deleted if this number is surpassed
define('KEEP_N_DAYS',0);        // How old can the oldest backup be in days. Older ones will be deleted
define('KEEP_N_GIGABYTES',0);   // How large can the sum of the backups per target (hostname) be

// FTP Settings if you want to use FTP
define("FTP_SSL",      false);
define("FTP_SERVER",   '');
define("FTP_PORT",     21);
define("FTP_USER",     '');
define("FTP_PASS",     '');
define("FTP_BASEDIR",  '/');


// S3 settings if you want backupdrop to save on S3 after upload
define('S3_BUCKET','');
define('S3_ACCESS_KEY','');
define('S3_SECRET_KEY','');
define('S3_ENDPOINT','');   // optional, if you use minio or other S3 compatible storage