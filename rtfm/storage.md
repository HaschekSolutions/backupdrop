# Storage controllers

BackupDrop has an extention system that allows handling of multiple storage solutions or backends. If one or more storage controllers are configured, BackupDrop will push any newly uploaded files to that storage controller. The originally uploaded files will stay in BackupDrop's data directory.

If you use [BackupDrop-side encryption](/rtfm/encryption.md) it will be encrypted before moving the file to the storage controller.


## Alternative Folder

The ALT_FOLDER option will copy every uploaded file from BackupDrop to a local path of your choice. For example you can mount a NFS share on your server and configure the ALT_FOLDER variable to point to that folder.

|Option | value type | What it does|
|---                      | ---     | ---|
| ALT_FOLDER              | string  | All uploaded files will be copied to this location. This location can be a mounted network share (eg NFS or Samba, etc) |


## S3 (compatible) storage

You can also store all uploaded files on S3 or S3 compatible storage like [Minio](https://min.io/). But you need to install Amazon's S3 librarys first:

```bash
# To install dependencies
cd web/lib
composer install
```

|Option | value type | What it does|
|---                                | ---           | ---|
|S3_BUCKET                          | string        | Name of your [S3 bucket](https://aws.amazon.com/s3/) |
|S3_ACCESS_KEY                      | string        | Access key for your bucket|
|S3_SECRET_KEY                      | string        | Secret key for your bucket |
|S3_ENDPOINT                        | URL           | Server URL. If you're using S3 compatible software like [Minio](https://min.io/) you can enter the URL here |

## FTP

Oldschool, insecure and not that fast. But if you use it in combination with [Encryption](/rtfm/encryption.md) this could be OK I guess. I don't judge.
This probably requires the php-ftp` package but on some platforms it's included in the php-common package.

|Option | value type | What it does|
|---                      | ---         | ---|
|FTP_SERVER               | string      | IP or hostname of your FTP Server |
|FTP_PORT                 | int         | Port number of your FTP Server. Defaults to 21 |
|FTP_SSL                  | bool        | If your FTP server supports SSL-FTP (note: not sFTP! not the same), set it to true |
|FTP_USER                 | string      | FTP Username |
|FTP_PASS                 | string      | FTP Password |
|FTP_BASEDIR              | string      | Base path where files will be stored. Must end with / eg `/backups/backupdrop/` |
