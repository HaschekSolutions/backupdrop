<?php

/**
 * Config needed
 * 
 * S3_BUCKET
 * S3_ACCESS_KEY
 * S3_SECRET_KEY
 * (optional) S3_ENDPOINT
 */

class S3Storage implements StorageController
{
	private $s3;
	function connect(){
		$this->s3 = new Aws\S3\S3Client([
			'version' => 'latest',
			'region'  => 'us-east-1',
			'endpoint' => S3_ENDPOINT,
			'use_path_style_endpoint' => true,
			'credentials' => [
					'key'    => S3_ACCESS_KEY,
					'secret' => S3_SECRET_KEY,
				],
		]);
	}

    function isEnabled()
    {
		return (defined('S3_BUCKET') && S3_BUCKET &&
				defined('S3_SECRET_KEY') && S3_SECRET_KEY &&
				class_exists("Aws\\S3\\S3Client"));
    }
    
    function fileExists($hostname,$file)
    {
		if(!$this->s3)$this->connect();

		return $this->s3->doesObjectExist(S3_BUCKET,$file);
	}
	
	function getItems($dev=false)
	{
		if(!$this->s3)$this->connect();

		$KeyCount = 9999;
		$keys = 100;	//the amount of keys we'll receive per request. 1000 max but that times out sometimes
		$lastkey = false;
		$count = 0;
		$items = array();
		while($KeyCount>=$keys)
		{
			$objects = $this->s3->listObjectsV2([
				'Bucket' => S3_BUCKET,
				'MaxKeys'=> $keys,
				'StartAfter'=>($lastkey?$lastkey:'')
			]);

			++$count;
			foreach ($objects['Contents'] as $object){
				$lastkey = $object['Key'];
				$items[] = $lastkey;
			}

			if($dev===true) echo "      Got ".($count*$keys)." files                  \r";

			$KeyCount = $objects['KeyCount'];
		}

		return $items;
	}

    function pullFile($hostname,$file,$location)
    {
		if(!$this->s3)$this->connect();

		if(!$this->fileExists($hostname,$file)) return false;

		$this->s3->getObject([
			'Bucket' => S3_BUCKET,
			'Key'    => $this->filenameToKey($hostname,$file),
			'SaveAs' => $location
	   ]);
	   return true;
    }

    function pushFile($hostname,$source,$file)
    {
		if(!$this->s3)$this->connect();
		
		$this->s3->putObject([
			'Bucket' => S3_BUCKET,
			'Key'    => $this->filenameToKey($hostname,$file),
			'SourceFile' => $source
		]);

		return true;
    }

    function deleteFile($hostname,$file)
    {
		if(!$this->s3)$this->connect();

		$this->s3->deleteObject([
			'Bucket' => S3_BUCKET,
			'Key'    => $this->filenameToKey($hostname,$file)
		]);
	}
	
	// rewrites 2021-01-18 18.04.txt.enc
	//    to $hostname/2021/01/2021-01-18 18.04.txt.enc
	function filenameToKey($hostname,$filename)
	{
		$unix = strtotime(substr($filename,0,16));
        $year = date("Y",$unix);
        $month = date("m",$unix);        

        return implode('/',[$hostname,$year,$month,$filename]);
	}
}