<?php
//
require __DIR__.'/../vue-router/autoload.php';

use Chenos\ExecJs\VueRouter\App;
use Chenos\ExecJs\VueSimple\Vue;
use Spatie\Ssr\Renderer;
use Spatie\Ssr\Engines\Node;
use Spatie\Ssr\Engines\V8;

class View
{
private $data = array();

private $render = FALSE;

public function __construct()
{
    /*$tpl = Basedir . '/App/Templates/_header.phtml';
    if( file_exists($tpl) ) {
        foreach($GLOBALS as $key => $value ) {
            $$key = $value;
        }
        ob_start();
        require $tpl;
        $template = ob_get_contents();
        ob_end_clean();
        var_dump(strlen($template));
        return $template;
    } else {
        return false;
    } 
    */
}
public function vuetest($data){
    $app = new App();
    
    $path = $data["trimmedPath"];


    var_dump("Path ------".$path);
    if($path =="Tasks"){
       $path =  "/Meetings/";
    }else{
        $path = "/".$path;
    }
    //$html = $app->respond($_SERVER['REQUEST_URI']);
    $html = $app->respond("/Tasks/");
    var_dump($html);
    $phtml["html"] =  $html;
    $phtml["title"] ="Tasks";
    //return $html;
    $output =  self::load('template',$phtml);
    //var_dump($output);

    return $output;
    
}

public function vuerender(){

    require_once 'Renderer.php';

    $renderer = new \Renderer(Basedir . '/App/vue-router/node_modules/');

    $html = $renderer->render(Basedir . '/App/vue-router/build/server.compiled.js', []);

    echo $html;


}

public function vue25(){
    
    $engine = new Node("node", "/home/kanagu/node/App/Views/temp");
    $renderer = new Renderer($engine);
    $context = [
        'message' => 'Test Prerendered Content!'
    ];

    //var_dump($context);

    $rendered = $renderer
        ->context($context)
        ->entry(Basedir . '/App/vue-router/build/server.compiled.js')
        ->render();

    //var_dump($rendered);

    if($rendered){
        $phtml["html"] =  $rendered;
        $phtml["title"] ="All Tasks";
        return  self::load('template',$phtml);
    }else{
        return "<h1>Error</h1>";
    }
}

public function render(){

    var_dump("Hello I am here");

    $engine = new V8();

$renderer = new Renderer($engine);

    $output = $renderer
    ->env('NODE_ENV', 'production')
    ->entry(Basedir . '/App/vue-router/js/test.js')
    ->render();


    var_dump($output); 

    return $output;
    /*$path ="/about";
    $renderer_source = file_get_contents(Basedir . '/App/new/node_modules/vue-server-renderer/basic.js');

    $app_source = file_get_contents(Basedir . '/App/new/js/app.js');

    $v8 = new \V8Js();

    ob_start();

    $js = <<<EOT

var process = { env: { VUE_ENV: "server", NODE_ENV: "production" } }; 

this.global = { process: process }; 

var url = "$path";

EOT;

    $v8->executeString($js);

    $v8->executeString($renderer_source);

    $v8->executeString($app_source);

    $ret =  ob_get_clean();

    var_dump($ret);*/
}

public function vueindex(){
    $phtml["title"] ="Login";
    return  self::load('login',$phtml);
}

public function vuetest3(){
    /*$engine = new Node("node", "/home/kanagu/node/App/Views/temp");

    $renderer = new Renderer($engine);

    $context = [
        'message' => 'Test Prerendered Content----!'
    ];

    $renderer->entry(Basedir . '/App/vue-router/build/server.compiled.js');

    $renderer->run();

    var_dump($renderer);*/

    
    
  

    /* var_dump("hello I am here");
    $vue_source = file_get_contents(__DIR__.'/js/Vue.js');
    $vuerouter = file_get_contents(Basedir . '/App/vue-router/node_modules/vue-router/dist/vue-router.common.js');
    $axios = file_get_contents(Basedir . '/App/vue-router/node_modules/axios/dist/axios.js');
    //$renderer_source = file_get_contents(__DIR__.'/js/basic.js');
    $renderer_source = file_get_contents(Basedir . '/App/vue-router/node_modules/vue-server-renderer/basic.js');
    //$app_source = file_get_contents(Basedir . '/App/vue-router/node_modules/vue-server-renderer/basic.js');
    $app_source = file_get_contents(Basedir . '/App/vue-router/js/server.js');
*/
  
    $app_source = file_get_contents(Basedir . '/App/vue-router/js/server.js');  
    $vue_source = file_get_contents(__DIR__.'/js/Vue.js');
    $renderer_source = file_get_contents(Basedir . '/App/vue-router/node_modules/vue-server-renderer/basic.js');
    $v8 = new \V8Js();

    $path ="/Tasks";
    ob_start();
    try {
        $v8->executeString("var process = { env: { VUE_ENV: 'server', NODE_ENV: 'production' }}; this.global = { process: process }; var url = '$path'");
        $v8->executeString($renderer_source);
        //$v8->executeString($app_source);
        //$v8->executeString($vue_source);
        //$v8->executeString($renderer_source);
    
        $v8->executeString($app_source);
    } catch(V8JsException $e) {
        echo $e;
      echo "
        File: {$e->getJsFileName()} \n
        Line Number: {$e->getJsLineNumber()} \n
        Source Line: {$e->getJsSourceLine()} \n
        Trace: {$e->getJsTrace()}
      ";
    }

    $ret =  ob_get_clean();
    var_dump($ret);

    
   

    //$phtml["context"] =  $context;
    //$phtml["rendered"] =  $rendered;
    //return  self::load('spatie',$phtml);
    
}

public function vuetest2(){
    //$app = new App();
    // $path = $data["trimmedPath"];
    /*if($path =="Tasks"){
       $path =  "/vue-router/";
    }else{*/
    //    $path = "/".$path;
    //}
    //$html = $app->respond("/Tasks/");
    //var_dump($html);

    //$html = $app->respond($path);
    $nodeJsPath =Basedir . '/App/vue-router/';
    //var_dump($nodeJsPath);
    $html = exec("cd ". $nodeJsPath. " && node test.js 2>&1", $out, $err);
    //var_dump($html);
    //var_dump($html);
    $phtml["html"] =  $html;
    $phtml["title"] ="All Tasks";
    return  self::load('template',$phtml);

    

}
public function vuetest1($result){
    $vue = new Vue();
    ob_start();
    $vue->render(Basedir . '/App/Vue2api/test.js');
    $phtml["html"] = ob_get_clean();
    var_dump($phtml["html"]);
    ob_start();
    $vue->render(Basedir . '/App/Vue/js/menu.js',['menus'=>['Home','Companies','Tasks', 'Meetings']]);
    $phtml["menu"] = ob_get_clean();
    //var_dump($phtml);
    $phtml["title"] ="Meetings";
    return  self::load('template',$phtml);
}

public function assign($variable, $value)
{
    $this->data[$variable] = $value;
}



public function __destruct()
{
    extract($this->data);
    if (is_readable($this->render)) include($this->render);

}

public function load($tpl, $arr = array(), $return = false ) {
    $tpl = Basedir . '/App/Templates/' . strtolower($tpl) . '.phtml';
    if( file_exists($tpl) ) {
        foreach( $arr as $key => $value ) {
            $$key = $value;
        }
        unset( $arr );

        foreach($GLOBALS as $key => $value ) {
            $$key = $value;
        }

        ob_start();
        require $tpl;
        $template = ob_get_contents();
        ob_end_clean();

        var_dump(strlen($template));

        if( $return == false ) {
            return $template;
        } else {
            return $template;
        }
    } else {
        return false;
    }
}

public function vue(){
    return this;
}




}
?>