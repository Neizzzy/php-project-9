<?php

use DI\Container;
use Hexlet\Code\Controller\PageController;
use Hexlet\Code\Controller\UrlCheckController;
use Hexlet\Code\Controller\UrlController;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpNotFoundException;
use Slim\Factory\AppFactory;
use Slim\Flash\Messages;
use Slim\Middleware\MethodOverrideMiddleware;
use Slim\Psr7\Response;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;

/** @var Psr\Container\ContainerInterface $this */

require __DIR__ . '/../vendor/autoload.php';

session_start();

if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
}

$container = new Container();

$container->set(Twig::class, function () {
    return Twig::create(__DIR__ . '/../templates', ['cache' => false]);
});

$container->set('flash', function () {
    return new Messages();
});

$container->set(\PDO::class, function () {
    try {
        $databaseUrl = $_ENV['DATABASE_URL'];
        if (!$databaseUrl) {
            throw new RuntimeException('DATABASE_URL environment is not defined');
        }

        $params = parse_url($_ENV['DATABASE_URL']);
        if (!isset($params['host'], $params['path'], $params['user'], $params['pass'])) {
            throw new RuntimeException('Invalid DATABASE_URL params');
        }

        $dsn = sprintf(
            "pgsql:host=%s;port=%s;dbname=%s;user=%s;password=%s",
            $params['host'],
            $params['port'] ?? 5432,
            ltrim($params['path'], '/'),
            $params['user'],
            $params['pass']
        );

        $conn = new \PDO($dsn);
        $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        return $conn;
    } catch (\PDOException $e) {
        error_log("Database Error: " . $e->getMessage());
        throw $e;
    } catch (RuntimeException $e) {
        error_log("Runtime Error: " . $e->getMessage());
        throw $e;
    }
});

$app = AppFactory::createFromContainer($container);

$app->add(TwigMiddleware::create($app, $container->get(Twig::class)));
$app->addRoutingMiddleware();
$app->add(MethodOverrideMiddleware::class);

$errorMiddleware = $app->addErrorMiddleware(true, true, true);
$errorMiddleware->setErrorHandler(
    [RuntimeException::class, PDOException::class],
    function (Request $request, Throwable $exception) {
        error_log($exception->getMessage());

        $twig = $this->get(Twig::class);
        $response = new Response();

        return $twig->render($response, 'errors/500.html.twig')->withStatus(500);
    }
);

$errorMiddleware->setErrorHandler(
    HttpNotFoundException::class,
    function () {
        $twig = $this->get(Twig::class);
        $response = new Response();

        return $twig->render($response, 'errors/404.html.twig')->withStatus(404);
    }
);

$container->set('router', function () use ($app) {
    return $app->getRouteCollector()->getRouteParser();
});

$app->get('/', PageController::class . ':home')->setName('home');

$app->get('/urls', UrlController::class . ':index')->setName('urls.index');
$app->post('/urls', UrlController::class . ':store')->setName('urls.store');
$app->get('/urls/{id}', UrlController::class . ':show')->setName('urls.show');

$app->post('/urls/{url_id}/checks', UrlCheckController::class . ':store')->setName('checks.store');

$app->run();
