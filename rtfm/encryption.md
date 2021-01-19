# Encryption in BackupDrop

You really should encrypt before uploading it to BackupDrop but if that's not an option, we've got you covered.

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