<?php 
/**
 * StorageController interface
 * 
 * Must be implemented by all storage systems
 */

interface StorageController
{
    /**
     * Checks if this storage system is enabled. 
     * For example check if all depenencies are met
     * or config vars are set
     * 
     * @return bool 
     */
    function isEnabled();
    
    /**
     * Is fired whenever a file is not found locally
     * Use this to look in your storage system for the file
     * 
     * @param string $file is  the file of the file requested
     * 
     * @return bool 
     */
    function fileExists($hostname,$file);


    /**
     * Returns an array of all items in this storage controller
     * 
     * @return array
     */
    function getItems();

    /**
     * If a file does exist in this storage system, then this method should
     * get the file and put it in the default data directory
     * 
     * The file should be placed in /data/$file/$file where the first $file is obviously
     * a folder that you might have to create first before putting the file in
     * 
     * @param string $file is the name of the file that should be pulled from this storage system
     * @param string $location is the location where the downloaded file should be placed
     * 
     * @return bool true if successful
     */
    function pullFile($hostname,$file,$location);

    /**
     * Whenever a new file is uploaded this method will be called
     * You should then upload it or do whatever your storage system is meant to do with new files
     * 
     * @param string $file is the name of the new file. The file path of this file is always ROOT.DS.'data'.DS.$file.DS.$file
     * 
     * @return bool true if successful
     */
    function pushFile($hostname,$source,$file);

    /**
     * If deletion of a file is requested, this method is called
     * 
     * @param string $file is the name of the file. Delete this file from your storage system
     * 
     * @return bool true if successful
     */
    function deleteFile($hostname,$file);
}