<?php
define('FILE_ENCRYPTION_BLOCKS', 10000);

/**
 * From: https://riptutorial.com/php/example/25499/symmetric-encryption-and-decryption-of-large-files-with-openssl
 */
class Encryption
{
    /**
     * Define the number of blocks that should be read from the source file for each chunk.
     * For 'AES-128-CBC' each block consist of 16 bytes.
     * So if we read 10,000 blocks we load 160kb into memory. You may adjust this value
     * to read/write shorter or longer chunks.
     */


    /**
     * Encrypt the passed file and saves the result in a new file with ".enc" as suffix.
     * 
     * @param string $source Path to file that should be encrypted
     * @param string $key    The key used for the encryption
     * @param string $dest   File name where the encryped file should be written to.
     * @return string|false  Returns the file name that has been created or FALSE if an error occured
     */
    function encryptFile($source, $key, $dest,$pubkey=false)
    {
        if($pubkey!==true)
            $key = substr(sha1($key, true), 0, 16);
        $iv = openssl_random_pseudo_bytes(16);

        if($pubkey===true)
            $blocklength = 245; //due to the nature of the algorithm we can only encrypt 245
        else
            $blocklength = 16 * FILE_ENCRYPTION_BLOCKS;

        $error = false;
        if ($fpOut = fopen($dest, 'w')) {
            // Put the initialzation vector to the beginning of the file
            fwrite($fpOut, $iv);
            if ($fpIn = fopen($source, 'rb')) {
                while (!feof($fpIn)) {
                    $plaintext = fread($fpIn, $blocklength);
                    if($pubkey===true)
                    {
                        if(!openssl_public_encrypt($plaintext,$ciphertext,$key))
                            return false;
                    }
                    else
                        $ciphertext = openssl_encrypt($plaintext, 'AES-128-CBC', $key, OPENSSL_RAW_DATA, $iv);
                    // Use the first 16 bytes of the ciphertext as the next initialization vector
                    $iv = substr($ciphertext, 0, 16);
                    fwrite($fpOut, $ciphertext);
                }
                fclose($fpIn);
            } else {
                $error = true;
            }
            fclose($fpOut);
        } else {
            $error = true;
        }

        return $error ? false : $dest;
    }

    /**
     * Dencrypt the passed file and saves the result in a new file, removing the
     * last 4 characters from file name.
     * 
     * @param string $source Path to file that should be decrypted
     * @param string $key    The key used for the decryption (must be the same as for encryption)
     * @param string $dest   File name where the decryped file should be written to.
     * @return string|false  Returns the file name that has been created or FALSE if an error occured
     */
    function decryptFile($source, $key, $dest,$pubkey=false)
    {
        if($pubkey!==true)
            $key = substr(sha1($key, true), 0, 16);

        if($pubkey===true)
            $blocklength = 245; //due to the nature of the algorithm we can only encrypt 245
        else
            $blocklength = 16 * FILE_ENCRYPTION_BLOCKS;

        $error = false;
        if ($fpOut = fopen($dest, 'w')) {
            if ($fpIn = fopen($source, 'rb')) {
                // Get the initialzation vector from the beginning of the file
                $iv = fread($fpIn, 16);
                while (!feof($fpIn)) {
                    $ciphertext = fread($fpIn, $blocklength + 1); // we have to read one block more for decrypting than for encrypting
                    if($pubkey===true)
                    {
                        openssl_private_decrypt($ciphertext, $plaintext , $key);
                    }
                    else
                        $plaintext = openssl_decrypt($ciphertext, 'AES-128-CBC', $key, OPENSSL_RAW_DATA, $iv);
                    // Use the first 16 bytes of the ciphertext as the next initialization vector
                    $iv = substr($ciphertext, 0, 16);
                    fwrite($fpOut, $plaintext);
                }
                fclose($fpIn);
            } else {
                $error = true;
            }
            fclose($fpOut);
        } else {
            $error = true;
        }

        return $error ? false : $dest;
    }

    function checkAge()
    {
        $age = shell_exec('which age');
        if($age)
            return true;
        return false;
    }

    function encryptAge($source, $dest){
        if(!$this->checkAge())
            throw new Exception('age not found');
        $pubkey = defined('ENCRYPTION_AGE_SSH_PUBKEY') && ENCRYPTION_AGE_SSH_PUBKEY != '' ? ENCRYPTION_AGE_SSH_PUBKEY : false;
        $sshpubkey = defined('ENCRYPTION_AGE_PUBKEY') && ENCRYPTION_AGE_PUBKEY != '' ? ENCRYPTION_AGE_PUBKEY : false;
        
        if(!$pubkey && !$sshpubkey)
            throw new Exception('No pubkeys configured');

        $cmd = ['age'];

        if($pubkey)
            $cmd[] = '-r '.escapeshellarg(ENCRYPTION_AGE_SSH_PUBKEY);
        if($sshpubkey)
            $cmd[] = '-r '.escapeshellarg(ENCRYPTION_AGE_PUBKEY);

        $cmd[] = '-o '.escapeshellarg($dest);
        $cmd[] = escapeshellarg($source);


        $cmd = implode(' ',$cmd);

        shell_exec($cmd);

        if(file_exists($dest) && filesize($dest) > 0)
            return true;
        return false;
    }
}
