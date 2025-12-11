# Encryption in BackupDrop

You really should encrypt before uploading it to BackupDrop but if that's not an option, we've got you covered.

If you are encrypting on your machine I'd reccomend [Age](https://github.com/FiloSottile/age). It's awesome and easy to use and you can encrypt files by SSH public keys (so the encrypting machine never needs to have the private key needed to decrypt). But BackupDrop comes with age support so all uploads can be encrypted using an SSH public key or an age public key (or both).

## Method 1: BackupDrop builtin age support
To use age in BackupDrop you only have to set one of two (or both) configuration options in the config file:

- ENCRYPTION_AGE_SSH_PUBKEY
- ENCRYPTION_AGE_PUBKEY

If you configure both entries then all uploads will be encrypted against both, your SSH public key and your age public key which means you can decrypt the backups with both of your (private) keys.

### SSH key based encryption
You can put your SSH keys public key in the `ENCRYPTION_AGE_SSH_PUBKEY` option and age will automatically encrypt all uploads against your key.

For example if you upload `secrets.txt`, it will be stored as `secrets.txt.age` in the data directory. To decrypt you can run `age -d -i ~/.ssh/id_rsa secrets.txt.age > secrets.txt`

Read more about age and SSH key based encryption [here](https://github.com/FiloSottile/age?tab=readme-ov-file#ssh-keys)

### age public key encryption
You can generate an age public and private key by running `age-keygen -o key.txt` which will generate a key file and print out the public key. You can put this public key in the config option `ENCRYPTION_AGE_PUBKEY` and all uploads will automatically be encrypted against your key.

For example if you upload `secrets.txt`, it will be stored as `secrets.txt.age` in the data directory. To decrypt you can run `age --decrypt -i key.txt secrets.txt.age > secrets.txt`

## Method 1: Encrypt using Public Key
This method should only be used on **smaller files**. Because of the nature of the algorithm we can only encrypt 245 characters at a time which means encrypting of large files will be painfully slow.

But this is a nice way to encrypt a backup without the uploading machine knowing the private key for decryption, which is awesome.

Just upload your file and add your publik key as a POST variabe called `pub_key` to the request.

```bash
curl -s -F "file=@webserver.tar.gz" -F "pub_key=-----BEGIN PUBLIC KEY-----            
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAxbAjSV+qk6qzSs0HA3+v
YCxgMDvKGzIV/ZLGimzKonbEeEWGOpZLdi99ISloAEd80miBC0CPjPqzJUfUjZlk
ukJ1C2q/NQJuqV4X52YSQNm7xVl78nTfzF7WQwQwzN5cXSpcPeSbnNqMo0u3Y37I
SnmrtygrSqpyc62O3LDFk20PecTWU0MVnC1FdnfM2z0xfZo3/pNc2mAeBClRi2Ct
HIUmbNcKP7wvrnjyPpW18wyHkLCF1vaEUYsUqcH5wFKVQ1GXW79b9Hik9bq4xvxh
20ixarq7iwb77qm1fj2dTmuMVI5RXNTnDSP2hB6bQPYKeL2gq6FjuiwmYh099sNj
zwIDAQAB
-----END PUBLIC KEY-----" http://localhost:8080/webserver
```

## Method 2: Encrypt using a password
This method is better for larger files although still pretty slow for big backups.

To use a password to encrypt, send it via the POST variable `enc_key`.

```bash
curl -s -F "file=@webserver.tar.gz" -F "enc_key=mystrongpassword" http://localhost:8080/webserver
```

Response

```json
{
  "status": "ok",
  "filename": "2021-01-18 17.55.txt.enc",
  "cleanup": []
}
```

As you can see by the response, the file has added the `.enc` suffix to indicate it's been encrypted