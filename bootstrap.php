<?php

use Silex\Application;
use Silex\Extension\HttpCacheExtension;
use Silex\Extension\MonologExtension;
use Silex\Extension\SessionExtension;
use Silex\Extension\SymfonyBridgesExtension;
use Silex\Extension\UrlGeneratorExtension;
use Silex\Extension\TwigExtension;

use Symfony\Component\ClassLoader\UniversalClassLoader;

require_once __DIR__.'/vendor/symfony/src/Symfony/Component/ClassLoader/UniversalClassLoader.php';
$loader = new UniversalClassLoader();
$loader->registerNamespaces(array(
    'Silex'   => __DIR__.'/vendor/silex/src',
    'Symfony' => __DIR__.'/vendor/symfony/src'
));
$loader->registerPrefixes(array('Pimple' => __DIR__.'/vendor/pimple/lib'));
$loader->register();

$app = new Application();

// Parameters
$app['debug'] = in_array($_SERVER['REMOTE_ADDR'], array('127.0.0.1', '::1'));
$app['base.path']   = __DIR__;
$app['app.path']    = $app['base.path'].'/app';
$app['cache.path']  = $app['base.path'].'/cache';
$app['logs.path']   = $app['base.path'].'/logs';
$app['src.path']    = $app['base.path'].'/src';
$app['tests.path']  = $app['base.path'].'/tests';
$app['vendor.path'] = $app['base.path'].'/vendor';
$app['web.path']    = $app['base.path'].'/web';
$app['twig.cache.path'] = $app['cache.path'].'/twig';

// Extensions
$app->register(new SessionExtension());

$app->register(new UrlGeneratorExtension());

$app->register(new SymfonyBridgesExtension(), array(
    'symfony_bridges.class_path' => $app['vendor.path'].'/symfony/src'
));

$app->register(new TwigExtension(), array(
    'twig.path' => $app['src.path'].'/views',
    'twig.options' => array('debug' => true, 'strict_variables' => true),
	'twig.class_path' => $app['vendor.path'].'/twig/lib',
    'twig.configure' => $app->protect(function ($twig) use ($app) {
        if (!$app['debug']) {
            $twig->setCache($app['twig.cache.path']);
        }
    }),
));

// Services
$app['memcached.server'] = array('localhost', 11211);
$app['memcached'] = $app->share(function () use ($app) {
    $m = new Memcache;
    call_user_func_array(array($m, 'addServer'), $app['memcached.server']);
    return $m;
});

return $app;
