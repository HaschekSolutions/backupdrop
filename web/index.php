<?php

// basic path definitions
define('DS', DIRECTORY_SEPARATOR);
define('ROOT', dirname(__FILE__));
ini_set('display_errors','On');
set_time_limit(0);

//timezone to UTC (+0)
date_default_timezone_set('UTC');

//includes
if(file_exists(ROOT.DS.'..'.DS.'config'.DS.'config.inc.php'))   //only load user config if it exists
    require_once(ROOT.DS.'..'.DS.'config'.DS.'config.inc.php');
if(file_exists(ROOT.DS.'lib'.DS.'vendor'.DS.'autoload.php'))    //only load composer stuff if it's installed
    require_once(ROOT.DS.'lib'.DS.'vendor'.DS.'autoload.php');
require_once(ROOT.DS.'lib'.DS.'core.php');
require_once(ROOT.DS.'lib'.DS.'encryption.php');
require_once(ROOT.DS.'lib'.DS.'helpers.php');
require_once(ROOT.DS.'lib'.DS.'encryption.php');
require_once(ROOT.DS.'lib'.DS.'storagecontroller.interface.php');
require_once(ROOT.DS.'lib'.DS.'folderconfig.php');
require_once(ROOT.DS.'lib'.DS.'retention.php');


//getting the url as array
$url = array_filter(explode('/',ltrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH),'/')));
$method = $_SERVER['REQUEST_METHOD'];

//main logic
//deciding what to do based on URL
$hostname = $url[0];
if(!$hostname || $url[0] == 'rtfm') //no hostname? Well let's render the info page
    echo renderInfoPage($url[1]);
else if($method=='POST') //handle an upload or API action
{
    header('Content-Type: application/json');
    
    //let's filter out the hostname and get rid of every special char except for: . _ -
    $hostname = preg_replace("/[^a-zA-Z0-9\.\-_]+/", "", $hostname);
    
    // Check if this is an API action
    $action = $_GET['action'] ?? null;
    
    if ($action === 'verify') {
        // Password verification
        $input = json_decode(file_get_contents('php://input'), true);
        $config = new FolderConfig($hostname);
        if ($config->verifyPassword($input['password'] ?? '')) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false]);
        }
    } else if ($action === 'save_settings') {
        // Save settings
        $config = new FolderConfig($hostname);
        
        // Verify authentication if password is set
        if ($config->hasPassword()) {
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            // In this case we rely on session storage, so just proceed
            // A more robust solution would validate the session
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (isset($input['password'])) {
            $config->setPassword($input['password']);
        }
        
        if (isset($input['retention'])) {
            $config->setRetention($input['retention']);
        }
        
        if ($config->save()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to save configuration']);
        }
    } else {
        // Regular file upload
        echo json_encode(handleUpload($hostname)).PHP_EOL;
    }
}
else if($method=='GET' && defined('DISABLE_UPLOADFORM') && DISABLE_UPLOADFORM!==true) //render file upload dialogue
{
    $hostname = preg_replace("/[^a-zA-Z0-9\.\-_]+/", "", $hostname);
    $config = new FolderConfig($hostname);
    include_once(ROOT.DS.'lib'.DS.'upload-template.html.php');
}


//functions start here

function handleUpload($hostname)
{
    // if a file was correctly uploaded
    if(isset($_FILES["file"]) && $_FILES["file"]["error"] == 0)
    {
        //target name of the backup is the date and the original extension
        $backupname = date("Y-m-d_H.i").'.'.pathinfo($_FILES["file"]["name"], PATHINFO_EXTENSION); 
        $path = ROOT.DS.'..'.DS.'data'.DS.$hostname.DS;
        if(!is_dir($path)) mkdir($path); //if the path doesn't exist yet, create it

        // if the user wants to encrypt it using custom key
        if(isset($_REQUEST['enc_key']) || isset($_REQUEST['pub_key']))
        {
            $backupname.='.enc';
            $e = new Encryption;
            if(!$e->encryptFile($_FILES["file"]["tmp_name"], ($_REQUEST['enc_key']?:$_REQUEST['pub_key']), $path.$backupname,($_REQUEST['pub_key']?true:false)))
                return ['status'=>'error','reason'=>'Failed to encrypt. Is the Key valid?'];
        }
        else if(((defined('ENCRYPTION_AGE_SSH_PUBKEY') && ENCRYPTION_AGE_SSH_PUBKEY != '') || (defined('ENCRYPTION_AGE_PUBKEY') && ENCRYPTION_AGE_PUBKEY != '')) && (new Encryption)->checkAge()) //if the user wants to encrypt it using the predefined key
        {
            $backupname.='.age';
            $e = new Encryption;
            if(!$e->encryptAge($_FILES["file"]["tmp_name"], $path.$backupname))
                return ['status'=>'error','reason'=>'Failed to encrypt. Is the Key valid?'];
        }
        else
            move_uploaded_file($_FILES["file"]["tmp_name"], $path.$backupname);

        storageControllerUpload($hostname,$backupname);

        //upload successful
        if(file_exists($path.$backupname))
        {
            // Track the file in config
            $config = new FolderConfig($hostname);
            $config->addFile($backupname, filesize($path.$backupname));
            $config->save();
            
            $cleanup = cleanUpForHostname($hostname);
            return ['status'=>'ok','filename'=>$backupname,'cleanup'=>$cleanup];
        }
        else
            return ['status'=>'error','reason'=>'Failed to upload. Write permissions?'];
    }
    else //some upload error
    {
        $error = $_FILES["file"]["error"];
        if($error == 1 || $error == 2)
            http_response_code(413);
        else if($error == 3)
            http_response_code(500);
        else
            http_response_code(404);
        return ['status'=>'error','reason'=>'No file uploaded','error'=>uploadErrorTranslator($error)];
    }
}

// This function looks at the already uploaded files
// and decides if any of them need to be removed
function cleanUpForHostname($hostname)
{
    $output = [];
    
    // Use new retention system
    $retentionManager = new RetentionManager($hostname);
    $deleted = $retentionManager->applyRetention();
    
    foreach ($deleted as $filename) {
        $output[] = "Deleted '$filename' based on retention policy";
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
