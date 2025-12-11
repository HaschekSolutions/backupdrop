<?php

function getStorageControllers()
{
    $controllers = array();
    if ($handle = opendir(ROOT.DS.'lib'.DS.'storage-controllers')) {
        while (false !== ($entry = readdir($handle))) {
            if ($entry != "." && $entry != "..") {
                if(endswith($entry,'.controller.php'))
                {
                    $controllers[] = ucfirst(substr($entry,0,-15)).'Storage';
                    include_once(ROOT.DS.'lib'.DS.'storage-controllers'.DS.$entry);
                }
            }
        }
        closedir($handle);
    }

    return $controllers;
}

function storageControllerUpload($hostname,$file)
{
    $sc = getStorageControllers();
    $filepath = ROOT.DS.'..'.DS.'data'.DS.$hostname.DS.$file;
    foreach($sc as $contr)
    {
        $controller = new $contr();
        if($controller->isEnabled()===true)
            $controller->pushFile($hostname,$filepath,$file);  
    }
}

function storageControllerDelete($hostname,$file)
{
    $sc = getStorageControllers();
    foreach($sc as $contr)
    {
        $controller = new $contr();
        if($controller->isEnabled()===true)
            $controller->deleteFile($hostname,$file);  
    }
}