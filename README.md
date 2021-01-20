<p align="center">
  <a href="" rel="noopener">
 <img height=200px src="https://pictshare.net/825l8k.png" alt="BackupDrop logo"></a>
</p>

<h1 align="center">BackupDrop</h1>

<div align="center">
 
  
![](https://img.shields.io/badge/php-7.4%2B-brightgreen.svg)
[![Apache License](https://img.shields.io/badge/license-Apache-brightgreen.svg?style=flat)](https://github.com/hascheksolutions/backupdrop/blob/master/LICENSE)
![HitCount](http://hits.dwyl.com/hascheksolutions/backupdrop.svg)
[![](https://img.shields.io/github/stars/hascheksolutions/backupdrop.svg?label=Stars&style=social)](https://github.com/hascheksolutions/backupdrop)

#### Selfhosted backup upload target that manages `versions` `encryption` `retention` and supports [cloud endpoints](/rtfm/storage.md)

</div>

-----------------------------------------

# Features
- Selfhostable
- Handles backup versions by date, size and count
- File based - no database needed
- Supports multiple [local and cloud endpoints](/rtfm/storage.md)
- Supports multiple backup sources (machines you want to back up), distinguished by hostname
- No dependencies unless you want cloud endpoints

# Basics

The idea is that you use BackupDrop to upload your backups, which (after upload) cannot be changed by the device you backed up. Some crypto lockers are actively looking for backup devices like NAS or backup drives and delete or encrypt them too.
Using BackupDrop the machine you backed up cannot delete or modify or even access past backups.

Also BackupDrop can handle multiple external [storage providers](/rtfm/storage.md) and save the backups on `S3`, `FTP` or `NFS`.

You can and should encrypt files before uploading them (especially if you're using cloud endpoints) but if you can't, BackupDrop can handle it for you using public key or password encryption. [Read more](/rtfm/encryption.md)

# Quick start with docker

```bash
docker run --rm --name backupdrop -p 8080:80 -it hascheksolutions/backupdrop
```

# Quick start without docker

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
If you upload the exact same file twice (detected by comparing their checksums), BackupDrop will delete the older upload and just keep the new one (only within the same backup source hostname)

```bash
curl -s -F "file=@webserver.tar.gz" http://localhost:8080/webserver
```

Result:
```json
{
  "status": "ok",
  "filename": "2021-01-18 00.24.gz",
  "cleanup": [
    "Deleted '2021-01-18 00.03.gz' because it's a duplicate"
  ]
}
```

## Configuration
To change default settings you need to copy or rename `/config/example.config.inc.php` to `/config/config.inc.php` and change the values as needed.

Check out [the example config file](/config/example.config.inc.php) to see what how you can configure BackupDrop.