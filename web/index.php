<?php

// global user settings
define('KEEP_N_BACKUPS',2);
define('KEEP_N_DAYS',0);
define('KEEP_N_GIGABYTES',0);

// basic path definitions
define('DS', DIRECTORY_SEPARATOR);
define('ROOT', dirname(__FILE__));
ini_set('display_errors','On');

//timezone to UTC (+0)
date_default_timezone_set('UTC');

//includes
if(file_exists(ROOT.DS.'..'.DS.'config'.DS.'config.inc.php'))
    require_once(ROOT.DS.'..'.DS.'config'.DS.'config.inc.php');
if(file_exists(ROOT.DS.'lib'.DS.'vendor'.DS.'autoload.php'))
    require_once(ROOT.DS.'lib'.DS.'vendor'.DS.'autoload.php');
require_once(ROOT.DS.'lib'.DS.'core.php');
require_once(ROOT.DS.'lib'.DS.'encryption.php');
require_once(ROOT.DS.'lib'.DS.'helpers.php');
require_once(ROOT.DS.'lib'.DS.'encryption.php');
require_once(ROOT.DS.'lib'.DS.'storagecontroller.interface.php');


//getting the url as array
$url = array_filter(explode('/',ltrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH),'/')));

//main logic
//deciding what to do based on URL
$hostname = $url[0];
if(!$hostname || $url[0] == 'rtfm') //no hostname? Well let's render the info page
    echo renderInfoPage($url[1]);
else //handle an upload
{
    header('Content-Type: application/json');
    
    //let's filter out the hostname and get rid of every special char except for: . _ -
    $hostname = preg_replace("/[^a-zA-Z0-9\.\-_]+/", "", $hostname);
    echo json_encode(handleUpload($hostname));
}


//functions start here

function handleUpload($hostname)
{
    // if a file was correctly uploaded
    if(isset($_FILES["file"]) && $_FILES["file"]["error"] == 0)
    {
        //target name of the backup is the date and the original extension
        $backupname = date("Y-m-d H.i").'.'.pathinfo($_FILES["file"]["name"], PATHINFO_EXTENSION); 
        $path = ROOT.DS.'..'.DS.'data'.DS.$hostname.DS;
        if(!is_dir($path)) mkdir($path); //if the path doesn't exist yet, create it

        // if the user wants to encrypt it
        if($_REQUEST['enc_key'] || $_REQUEST['pub_key'])
        {
            $backupname.='.enc';
            $e = new Encryption;
            if(!$e->encryptFile($_FILES["file"]["tmp_name"], ($_REQUEST['enc_key']?:$_REQUEST['pub_key']), $path.$backupname,($_REQUEST['pub_key']?true:false)))
                return ['status'=>'error','reason'=>'Failed to encrypt. Is the Key valid?'];
        }
        else
            move_uploaded_file($_FILES["file"]["tmp_name"], $path.$backupname);

        storageControllerUpload($hostname,$backupname);

        //upload successful
        if(file_exists($path.$backupname))
        {
            $cleanup = cleanUpForHostname($hostname);
            return ['status'=>'ok','filename'=>$backupname,'cleanup'=>$cleanup];
        }
        else
            return ['status'=>'error','reason'=>'Failed to upload. Write permissions?'];
    }
    else //some upload error
    {
        http_response_code(404);
        return ['status'=>'error','reason'=>'No file uploaded'];
    }
}

// This function looks at the already uploaded files
// and decides if any of them need to be removed
function cleanUpForHostname($hostname)
{
    $output = []; //what we want to return
    $hashes = []; //array of sha1 hashes of the files to find duplicates
    $count = 0;
    $sizesum = 0;

    $files = array_diff(scandir(ROOT.DS.'..'.DS.'data'.DS.$hostname.DS,SCANDIR_SORT_DESCENDING), array('..', '.'));
    if($files)
        foreach($files as $file)
        {
            $filepath = ROOT.DS.'..'.DS.'data'.DS.$hostname.DS.$file;
            $sizesum+=filesize($filepath);
            $sha1 = sha1_file($filepath);

            //if the file size exeeds the users wishes, delete it
            if(defined('KEEP_N_GIGABYTES') && KEEP_N_GIGABYTES > 0 && ($sizesum/pow(1024, 3))>KEEP_N_GIGABYTES) {
                $sizesum-=filesize($filepath); //take the size away since now we have less files in the folder
                unlink($filepath);
                storageControllerDelete($hostname,$file);
                $output[] = "Deleted '$file' because of user setting (keep max of ".KEEP_N_GIGABYTES." gigabytes of backups)";
            }
            //if there are more backups in the directory than the user wanted, delete it
            if(defined('KEEP_N_BACKUPS') && KEEP_N_BACKUPS > 0 && ++$count > KEEP_N_BACKUPS){
                unlink($filepath);
                storageControllerDelete($hostname,$file);
                $output[] = "Deleted '$file' because of user setting (keep max ".KEEP_N_BACKUPS." backups)";
            }
            //if the exact same file has been uploaded before, remove it
            else if(in_array($sha1,$hashes)){ 
                unlink($filepath);
                storageControllerDelete($hostname,$file);
                $output[] = "Deleted '$file' because it's a duplicate";
            }
            //if its older than we want it to be, delete it
            else if(defined('KEEP_N_DAYS') && KEEP_N_DAYS > 0 && (((time() - strtotime(substr($file,0,16))) / (3600*24))> KEEP_N_DAYS) ){
                unlink($filepath);
                storageControllerDelete($hostname,$file);
                $output[] = "Deleted '$file' because it's older than the user wants (max ".KEEP_N_DAYS." days)";
            }
            else // ok let's not delete this file
                $hashes[] = $sha1;
        }
    return $output;
}

// renders the welcome page
function renderInfoPage($file)
{
    include_once(ROOT.DS.'lib'.DS.'parsedown.php');
    $p = new Parsedown();
    if($file && file_exists(ROOT.DS.'..'.DS.'rtfm'.DS.preg_replace("/[^a-zA-Z0-9\.\-_]+/", "", $file)))
        $mdfile = ROOT.DS.'..'.DS.'rtfm'.DS.$file;
    else $mdfile = ROOT.DS.'..'.DS.'README.md';

    return $p->text(file_get_contents($mdfile));
}
