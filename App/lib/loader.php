<?php

//namespace App\lib;

spl_autoload_register(function ($className) {
    var_dump(__NAMESPACE__);
    var_dump($className);
    // Cut Root-Namespace
    $className = str_replace( __NAMESPACE__.'\\', '', $className );
    
    // Correct DIRECTORY_SEPARATOR
    $className = str_replace( array( '\\', '/' ), DIRECTORY_SEPARATOR, __DIR__.DIRECTORY_SEPARATOR.$className.'.php' );
    var_dump(realpath($className));
        var_dump($className);
    if (is_readable($className)) require_once $className;
    // Get file real path
    //if($className = realpath($className)===false){
        //var_dump("True");
        // File not found
    //    return false;
    //}else {
    //    require_once $className;
    //    return true;
    //}
});