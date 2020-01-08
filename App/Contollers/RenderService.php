<?php


class RenderService
{
    /**
     * string
     */
    private $nodeRoot;

    /**
     * string
     */
    private $jsRoot;

    public function __construct(string $rootDirectory)
    {
        $this->nodeRoot = sprintf('%s/App/node_modules', $rootDirectory);
       // var_dump($this->nodeRoot);
        $this->jsRoot = sprintf('%s/assets/js', $rootDirectory);
        //var_dump($this->jsRoot);
    }

    public function render($path): string
    {
        $vue = file_get_contents(sprintf('%s/vue/dist/vue.js', $this->nodeRoot));
        //var_dump($vue);
        $vueRenderer = file_get_contents(sprintf('%s/vue-server-renderer/basic.js', $this->nodeRoot));
        $Renderbuild = file_get_contents(sprintf('%s/vue-server-renderer/build.js', $this->nodeRoot));
        //var_dump($Renderbuild);
        $entryPoint = file_get_contents(sprintf('%s/app.js', $this->jsRoot));
         //var_dump($entryPoint);

        $v8 = new \V8Js();
        try {
            ob_start();

            $js =
                <<<EOT
var process = { env: { VUE_ENV: "server", NODE_ENV: "production" } }; 
this.global = { process: process };
let url = "$path";
EOT;

            $v8->executeString('var process = { env: { VUE_ENV: "server", NODE_ENV: "production" }}; this.global = { process: process };');
            $v8->executeString($vue);
            $v8->executeString($vueRenderer);
            $v8->executeString($Renderbuild);
            $v8->executeString($entryPoint);
            $result = ob_get_clean();
        } catch (\Exception $e) {
            $result = $e->getMessage();
        }

        return $result;
    }
}