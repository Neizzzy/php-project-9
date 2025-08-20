<?php

use DI\Container;
use Slim\Factory\AppFactory;
use Slim\Middleware\MethodOverrideMiddleware;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;

require __DIR__ . '/../vendor/autoload.php';

$container = new Container();
$container->set(Twig::class, function () {
    return Twig::create(__DIR__ . '/../templates', ['cache' => false]);
});

$app = AppFactory::createFromContainer($container);
$app->add(TwigMiddleware::create($app, $container->get(Twig::class)));
$app->addRoutingMiddleware();
$app->addErrorMiddleware(true, true, true);
$app->add(MethodOverrideMiddleware::class);

$app->get('/', function ($request, $response) {
    $twig = $this->get(Twig::class);
    return $twig->render($response, 'home.html.twig');
})->setName('home');

$app->run();
