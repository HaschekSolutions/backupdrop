<?php
/**
 * This simple script decrypts a already encrypted file
 * if it was encrypted using BackupDrop
 */

//definitions and requirements
define('DS', DIRECTORY_SEPARATOR);
define('ROOT', dirname(__FILE__));
require_once(ROOT.DS.'..'.DS.'web/lib'.DS.'encryption.php');

// this script should not be called via web server, just via command line
if(php_sapi_name() !== 'cli') exit('Run this script via command line, not via browser');


//
// EXAMPLE 1: Public key encrypted files
// 
// to decrypt a file that was encrypted using a public key, we 
// need the private key. The "true" parameter in the end of decryptFile
// let's the decryption algorithm know it's symmetric encryption

$privkey = '-----BEGIN RSA PRIVATE KEY-----
MIIEpAIBAAKCAQEAxbAjSV+qk6qzSs0HA3+vYCxgMDvKGzIV/ZLGimzKonbEeEWG
[..]
ERIB1wtjiFfSVn1fjFBJg2jf6huoGbGzW60hDQNEgkH366zLWZxE9w==
-----END RSA PRIVATE KEY-----';

$e = new Encryption;
//this call will decrypt the file /path/to/your/file.enc and save it as /path/to/the/decrypted/file.tar.gz
$e->decryptFile('/path/to/your/file.enc',$privkey,'/path/to/the/decrypted/file.tar.gz',true);



//
// EXAMPLE 2: Password encrypted files
// 
// Just specify the password and decrypt it this way. No magic here.
// the "false" in the end of decryptFile lets the algorithm know it's password and not public/private key

$password = 'yourawesomepassword';
$e = new Encryption;
//this call will decrypt the file /path/to/your/file.enc and save it as /path/to/the/decrypted/file.tar.gz
$e->decryptFile('/path/to/your/file.enc',$password,'/path/to/the/decrypted/file.tar.gz',false);