<p align="center">
  <a href="" rel="noopener">
 <img height=200px src="https://pictshare.net/825l8k.png" alt="BackupDrop logo"></a>
</p>

<h1 align="center">BackupDrop</h1>

<div align="center">
 
  
![](https://img.shields.io/badge/php-7.4%2B-brightgreen.svg)
[![Apache License](https://img.shields.io/badge/license-Apache-brightgreen.svg?style=flat)](https://github.com/geek-at/backupdrop/blob/master/LICENSE)
[![HitCount](http://hits.dwyl.io/geek-at/backupdrop.svg)](http://hits.dwyl.io/geek-at/backupdrop)
[![](https://img.shields.io/github/stars/geek-at/backupdrop.svg?label=Stars&style=social)](https://github.com/geek-at/backupdrop)

#### Selfhosted backup upload target that manages `versions` `encryption` `retention` and supports cloud endpoints

</div>

-----------------------------------------

# Features
- Selfhostable
- No dependencies unless you want cloud endpoints
- Handles backup versions by date and file extension
- File based - no database needed
- Supports multiple backup sources (machines you want to back up), distinguished by hostname

# Basics

The idea is that you use BackupDrop to upload your backups and cannot be changed by the device you backed up. Some crypto lockers are actively looking for backup devices like NAS or backup drives and delete or encrypt them too.
Using BackupDrop the machine you backed up cannot delete or modify or even access past backups.

Also BackupDrop can handle multiple external storage providers and save the backups on `S3`, `FTP` or `Samba`.

You can and should encrypt files before uploading them (especially if you're using cloud endpoints) but if you can't, BackupDrop can handle it for you using public key or password encryption. [Read more](/rtfm/encryption.md)

# Quick start

Start the server (in a prodction environment you'll want to use a real webserver like nginx)

```bash
cd web
php -S 0.0.0.0:8080 index.php
```

Then upload a file using curl
```bash
curl -s -F "file=@webserver.tar.gz" http://localhost:8080/webserver
```

Response: 
```json
{
  "status": "ok",
  "filename": "2021-01-18 00.03.gz",
  "cleanup": []
}
```

This has created the folder data/**webserver** (because we uploaded the file to localhost:8080/**webserver**) and renamed  the file to the timestamp and the original extension.

![](https://pictshare.net/z0snrz.png)


## Identical upload detection
If you upload the exact same file twice, BackupDrop will delete the older upload and just keep the new one (only within the same backup source hostname)

```
curl -s -F "file=@webserver.tar.gz" http://localhost:8080/webserver

Result:
{
  "status": "ok",
  "filename": "2021-01-18 00.24.gz",
  "cleanup": [
    "Deleted '2021-01-18 00.03.gz' because it's a duplicate"
  ]
}
```

## Configuration
At the moment you have three options (can be combined)

```php
define('KEEP_N_BACKUPS',0); //how many backups (files in folder) can there be at the same time
define('KEEP_N_DAYS',0); //how many days should backups be kept (only triggered on a new successful upload)
define('KEEP_N_GIGABYTES',0); //how many gigabytes will be kept. If a new upload causes the folder to contain more than this setting, the oldest one will be deleted
```