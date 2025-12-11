<?php 

/**
 * Needed config settings
 * 
 * FTP_SSL      true/false
 * FTP_SERVER   ip/hostname
 * FTP_PORT     ftp port
 * FTP_USER     username
 * FTP_PASS     password
 * FTP_BASEDIR  should probably be "/"
 */

class FTPStorage implements StorageController
{
    private $connection;
    private $login;

    function __destruct()
    {
        if($this->connection)
        ftp_close($this->connection);
    }

    function connect()
    {
        if(!$this->connection)
        {
            if(defined('FTP_SSL') && FTP_SSL === true)
                $this->connection = ftp_ssl_connect(FTP_SERVER, ((defined('FTP_SSL') && is_numeric(FTP_PORT))?FTP_PORT:21) );
            else
                $this->connection = ftp_connect(FTP_SERVER, ((defined('FTP_SSL') && is_numeric(FTP_PORT))?FTP_PORT:21) );
        }
        if($this->connection && !$this->login)
        {
            $this->login = ftp_login($this->connection, FTP_USER, FTP_PASS);
            ftp_pasv($this->connection, TRUE);
        }

        // Was the connection successful?
        if ((!$this->connection) || (!$this->login)) {
            $this->connection = false;
            return false;
        }
        return true;
    }

    function isEnabled()
    {
        return (defined('FTP_SERVER') && FTP_SERVER &&
        defined('FTP_USER') && FTP_USER &&
        defined('FTP_PASS') && FTP_PASS);
    }
    
    function fileExists($hostname,$file)
    {
        if(!$this->connect()) return null;
        $subdir = $this->fileToDir($hostname,$file);
        $ftpfilepath = FTP_BASEDIR.$subdir.'/'.$file;
        if(@ftp_chdir($this->connection, FTP_BASEDIR.$subdir))
            return (ftp_size($this->connection,$ftpfilepath)>0?true:false);
        return false;
    }

    function getItems($dev=false)
    {
        if(!$this->connect()) return false;
        return $this->ftp_list_files_recursive(FTP_BASEDIR,$dev);
    }

    function pullFile($hostname,$file,$location)
    {
        if(!$this->connect()) return false;
        $subdir = $this->fileToDir($hostname,$file);
        $ftpfilepath = FTP_BASEDIR.$subdir.'/'.$file;
        return ftp_get($this->connection, $location, $ftpfilepath, FTP_BINARY);
    }

    function pushFile($hostname,$source,$file)
    {
        if(!$this->connect()) return false;
        $subdir = $this->fileToDir($hostname,$file);
        $ftpfilepath = FTP_BASEDIR.$subdir.'/'.$file;
        $this->ftp_mksubdirs($subdir);

        return ftp_put($this->connection, $ftpfilepath, $source, FTP_BINARY);
    }

    function deleteFile($hostname,$file) 
    {
        if(!$this->connect()) return false;
        if(!$this->fileExists($hostname,$file)) return false;
        $subdir = $this->fileToDir($hostname,$file);
        $ftpfilepath = FTP_BASEDIR.$subdir.'/'.$file;
        return (ftp_delete($this->connection,$ftpfilepath)?true:false);
    }

    function fileToDir($hostname,$file)
    {
        $unix = strtotime(substr($file,0,16));
        $year = date("Y",$unix);
        $month = date("m",$unix);        

        return implode('/',[$hostname,$year,$month]);
    }

    function ftp_mksubdirs($ftpath)
    {
        if(!$this->connect()) return false;
        @ftp_chdir($this->connection, FTP_BASEDIR); 
        $parts = array_filter(explode('/',$ftpath), function($value) {
            return ($value !== null && $value !== false && $value !== ''); 
        });
        foreach($parts as $part){
            $part = strval($part);
        if(!@ftp_chdir($this->connection, $part)){
            ftp_mkdir($this->connection, $part);
            ftp_chdir($this->connection, $part);
        }
        }
    }

    function ftp_list_files_recursive($path,$dev=false)
    {
        if(!$this->connect()) return false;
        $items = ftp_mlsd($this->connection, $path);
        $result = array();

        if(is_array($items))
        foreach ($items as $item)
        {
            $name = $item['name'];
            $type = $item['type'];
            $filepath = $path.'/'. $name;

            if ($type == 'dir')
            {
                $result =
                    array_merge($result, $this->ftp_list_files_recursive($filepath,$dev));
            }
            else if($this->mightBeAfile($name))
            {
                $result[] = $name;
                if($dev===true) echo "      Got $name                  \r";
            }
        }
        
        return $result;
    }

    function mightBeAfile($name)
    {
        if(strlen($name)>16 && strtotime(substr($name,0,16))!==false) return true;
        return false;
    }
}