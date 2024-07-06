<?php
$app->detectEnvironment(function () use ($app) {
    if(isset($_SERVER['HTTP_HOST']) and !empty($_SERVER['HTTP_HOST'])){
        $pos = mb_strpos($_SERVER['HTTP_HOST'], '.');
        $prefix = '';
        if ($pos) {
            $prefix = mb_substr($_SERVER['HTTP_HOST'], 0, $pos);
        }
        $file = '.' . $prefix . '.env';
        if (!file_exists($app['path.base'] . '/' . $file)) {
            $file = '.env';
        }

        if(!empty($prefix)){
           $app->loadEnvironmentFrom($file);
        }
    }
});