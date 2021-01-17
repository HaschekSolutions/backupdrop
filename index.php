<?php

// user settings
define('KEEP_N_BACKUPS',0);
define('KEEP_N_GIGABYTES',0.5);

// basic path definitions
define('DS', DIRECTORY_SEPARATOR);
define('ROOT', dirname(__FILE__));
ini_set('display_errors','On');

//timezone to UTC (+0)
date_default_timezone_set('UTC');

//getting the url as array
$url = array_filter(explode('/',ltrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH),'/')));

//main logic
//deciding what to do based on URL
$hostname = $url[0];
if(!$hostname) //no hostname? Well let's render the info page
    echo renderInfoPage();
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
    if(isset($_FILES["file"]) && $_FILES["file"]["error"] == 0)
    {
        //target name of the backup is the date and the original extension
        $backupname = date("Y-m-d H.i").'.'.pathinfo($_FILES["file"]["name"], PATHINFO_EXTENSION); 
        $path = ROOT.DS.'data'.DS.$hostname.DS;
        if(!is_dir($path)) mkdir($path); //if the path doesn't exist yet, create it
        move_uploaded_file($_FILES["file"]["tmp_name"], $path.$backupname);

        //upload successful
        if(file_exists($path.$backupname))
        {
            $cleanup = cleanUpForHostname($hostname);
            return ['status'=>'ok','filename'=>$backupname,'cleanup'=>$cleanup];
        }
        else
            return ['status'=>'error','reason'=>'Failed to upload. Write permissions?'];
    }
    else
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

    $files = array_diff(scandir(ROOT.DS.'data'.DS.$hostname.DS,SCANDIR_SORT_DESCENDING), array('..', '.'));
    if($files)
        foreach($files as $file)
        {
            $filepath = ROOT.DS.'data'.DS.$hostname.DS.$file;
            $sizesum+=filesize($filepath);
            $sha1 = sha1_file($filepath);

            //if the file size exeeds the users wishes, delete it
            if(defined('KEEP_N_GIGABYTES') && KEEP_N_GIGABYTES > 0 && ($sizesum/pow(1024, 3))>KEEP_N_GIGABYTES) {
                unlink($filepath);
                $output[] = "Deleted '$file' because of user setting (keep max of ".KEEP_N_GIGABYTES." gigabytes of backups)";
            }
            //if there are more backups in the directory than the user wanted, delete it
            if(defined('KEEP_N_BACKUPS') && KEEP_N_BACKUPS > 0 && ++$count > KEEP_N_BACKUPS){
                unlink($filepath);
                $output[] = "Deleted '$file' because of user setting (keep max ".KEEP_N_BACKUPS." backups)";
            }
            //if the exact same file has been uploaded before, remove it
            else if(in_array($sha1,$hashes)){ 
                unlink($filepath);
                $output[] = "Deleted '$file' because it's a duplicate";
            }
            else
                $hashes[] = $sha1;
        }
    return $output;
}

// renders the welcome page
function renderInfoPage()
{
    return file_get_contents('welcome.html');
}
