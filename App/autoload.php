<?php

// autoload classes based on a 1:1 mapping from namespace to directory structure.
spl_autoload_register(function ($className) {

    # Usually I would just concatenate directly to $file variable below
    # this is just for easy viewing on Stack Overflow)
        $ds = DIRECTORY_SEPARATOR;
        $dir = __DIR__;

    // replace namespace separator with directory separator (prolly not required)
        $className = str_replace('\\', $ds, $className);
       // var_dump("Class Name :". $className);

    // get full name of file containing the required class
        $file = "{$dir}{$ds}{$className}.php";
        //var_dump("File Name : " . $file);
    // get file if it is readable
        if (is_readable($file)) require_once $file;
        
        spl_autoload_register(function($className) {
            $file = __DIR__ . '/Contollers/' . $className . '.php';
             //var_dump($file);
            if (is_readable($file)) include_once   $file;
        });
        spl_autoload_register(function($className) {
            $file = __DIR__ . '/Models/' . $className . '.php';
             //var_dump($file);
            if (is_readable($file)) include_once   $file;
        });
        spl_autoload_register(function($className) {
            $file = __DIR__ . '/Views/' . $className . '.php';
             //var_dump($file);
            if (is_readable($file)) include_once   $file;
        });
        spl_autoload_register(function($className) {
             $file = __DIR__ . '/Lib/' . $className . '.php';
              //var_dump($file);
            if (is_readable($file)) include_once   $file;
        });
        spl_autoload_register(function($className) {
             $file = __DIR__ . '/Lib/test' . $className . '.php';
              //var_dump($file);
            if (is_readable($file)) include_once   $file;
        });

        spl_autoload_register(function($className) {
             $file = __DIR__ . '/Spatie/' . $className . '.php';
              //var_dump($file);
            if (is_readable($file)) include_once   $file;
        });
        spl_autoload_register(function($className) {
             $file = __DIR__ . '/Spatie/Engines/' . $className . '.php';
              //var_dump($file);
            if (is_readable($file)) include_once   $file;
        });
        spl_autoload_register(function($className) {
             $file = __DIR__ . '/Spatie/Exceptions/' . $className . '.php';
              //var_dump($file);
            if (is_readable($file)) include_once   $file;
        });
        spl_autoload_register(function($className) {
             $file = Basedir . '/src/' . $className . '.php';
              var_dump($file);
            if (is_readable($file)) include_once   $file;
        });
        spl_autoload_register(function($className) {
             $file = Basedir . '/src/chenos/v8js-module-loader' . $className . '.php';
              //var_dump($file);
            if (is_readable($file)) include_once   $file;
        });
        spl_autoload_register(function($className) {
             $file = Basedir . '/src/webmozart/assers/src/' . $className . '.php';
              //var_dump($file);
            if (is_readable($file)) include_once   $file;
        });
        spl_autoload_register(function($className) {
             $file = Basedir . '/src/webmozart/path-util/src/' . $className . '.php';
              //var_dump($file);
            if (is_readable($file)) include_once   $file;
        });

});