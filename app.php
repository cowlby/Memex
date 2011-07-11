<?php

use Silex\Application;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;

$app = require_once __DIR__.'/bootstrap.php';

/**
 * Home: GET /
 * Shows various database statistics.
 */
$app->get('/', function () use ($app) {
    
    $stats = $app['memcached']->getStats();
    $stats['last_reboot'] = new DateTime(date('c', time() - $stats['uptime']));
    
    return new Response($app['twig']->render('index.html.twig', array(
        'stats' => $stats,
        'phpversion' => phpversion(),
        'host' => implode(':', $app['memcached.server'])
    )));
    
})->bind('home');

/**
 * Show Key: GET /keys/{key}
 * Retrieves the cache entry and displays it. 
 */
$app->get('/keys/{key}', function ($key) use ($app) {
    
    $data = $app['memcached']->get($key);
    
    if ($data === FALSE) {
        $app['session']->setFlash('error', sprintf('Key (%s) does not exist.', $key));
        return $app->redirect($app['url_generator']->generate('home'));
    }
    
    return new Response($app['twig']->render('get.html.twig', array(
        'key' => $key,
        'data' => var_export(unserialize($data), TRUE)
    )));
    
})->bind('key');

/**
 * Delete Key: DELETE /keys/{key}
 * Deletes the key specified. Accepts a key request parameter as well.
 */
$app->delete('/keys/{key}', function ($key) use ($app) {
    
    $key = $key === NULL ? $app['request']->request->get('key') : $key;
    
    if ($key === NULL) {
        $app['session']->setFlash('error', 'No cache key specified.');
    } else if ($app['memcached']->delete($key) === FALSE) {
        $app['session']->setFlash('error', sprintf('Key (%s) does not exist.', $key));
    } else {
        $app['session']->setFlash('success', sprintf('Key (%s) successfully deleted.', $key));
    }
    
    return $app->redirect($app['url_generator']->generate('home'));
    
})->value('key', NULL);

/**
 * Flush: GET /flush
 * Flushes the memcached server.
 */
$app->get('/flush', function () use ($app) {
    
    if ($app['memcached']->flush() === FALSE) {
        $app['session']->setFlash('error', 'Could not flush the memcached servers.');
    } else {
        $app['session']->setFlash('success', 'Successfully flushed the memcached servers.');
    }
    
    return $app->redirect($app['url_generator']->generate('home'));
    
})->bind('flush');

/**
 * Error Pages
 */
$app->error(function (\Exception $e) use ($app) {
    
    $code = $e instanceof HttpExceptionInterface ? $e->getStatusCode() : 500;
    
    return new Response($app['twig']->render('error.html.twig', array(
        'code' => $code,
        'message' => Response::$statusTexts[$code],
        'details' => $app['debug'] ? $e->getMessage() : NULL
    )), $code);
});

return $app;
