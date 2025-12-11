<?php 

/**
 * Altfolder just copies every uploaded file to another folder
 * This can be used to
 */
class AltfolderStorage implements StorageController
{
    function isEnabled()
    {
        return (defined('ALT_FOLDER') && ALT_FOLDER && is_dir(ALT_FOLDER));
    }
    
    function fileExists($hostname,$file)
    {
        $altname=ALT_FOLDER.DS.$file;
		return file_exists($altname);
    }

    function getItems($dev=false)
    {
        $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(ALT_FOLDER.DS));
        $files = array(); 

        foreach ($rii as $file) {
            if ($file->isDir())
                continue;
            $files[] = $file->getFilename(); 
        }

        return $files;
    }

    function pullFile($hostname,$file,$location)
    {
        $altname=ALT_FOLDER.DS.$file;
		if(file_exists($altname))
		{
            copy($altname,$location);
		}
    }

    function pushFile($hostname,$source,$file)
    {
        $altname=ALT_FOLDER.DS.$file;
		if(!$this->fileExists($file))
		{
            copy($source,$altname);
            return true;
        }
        
        return false;
    }

    function deleteFile($hostname,$file)
    {
        $altname=ALT_FOLDER.DS.$file;
		if(file_exists($altname))
		{
			unlink($altname);
		}
    }
}