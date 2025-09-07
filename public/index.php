<?php

use DI\Container;
use DiDom\Document;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Hexlet\Code\Model\Url;
use Hexlet\Code\Model\UrlCheck;
use Hexlet\Code\Repository\UrlCheckRepository;
use Hexlet\Code\Repository\UrlRepository;
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

$router = $app->getRouteCollector()->getRouteParser();

$app->get('/', function ($request, $response) {
    $twig = $this->get(Twig::class);
    return $twig->render($response, 'home.html.twig');
})->setName('home');

$app->get('/urls', function ($request, $response) {
    $urls = $this->get(UrlRepository::class)->urlsWithLastCheck();

    $params = ['urls' => $urls];
    $twig = $this->get(Twig::class);

    return $twig->render($response, 'urls/index.html.twig', $params);
})->setName('urls.index');

$app->post('/urls', function ($request, $response) use ($router) {
    $urlData = $request->getParsedBodyParam('url', '');

    $validator = new \Valitron\Validator($urlData);
    $validator->rule('required', 'name')->message('URL не должен быть пустым');
    $validator->rule('url', 'name')->message('Некорректный URL');
    $validator->rule('lengthMax', 'name', 255)->message('Слишком длинный URL');

    if ($validator->validate()) {
        $normalizedUrl = parse_url($urlData['name'], PHP_URL_SCHEME) . '://' .
            parse_url($urlData['name'], PHP_URL_HOST);

        $urlRepository = $this->get(UrlRepository::class);

        $sameName = $urlRepository->findByName($normalizedUrl);
        if ($sameName) {
            $this->get('flash')->addMessage('success', 'Страница уже существует');
            return $response->withRedirect($router->urlFor('urls.show', ['id' => $sameName->getId()]), 301);
        }

        $url = new Url($normalizedUrl);
        $urlRepository->create($url);

        $this->get('flash')->addMessage('success', 'Страница успешно добавлена');

        return $response->withRedirect($router->urlFor('urls.show', ['id' => $url->getId()]), 301);
    }

    $twig = $this->get(Twig::class);
    $params = [
        'errors' => $validator->errors(),
        'url' => $urlData
    ];

    return $twig->render($response, 'home.html.twig', $params)->withStatus(422);
})->setName('urls.store');

$app->get('/urls/{id}', function ($request, $response, $args) {
    $id = (int) $args['id'];
    $url = $this->get(UrlRepository::class)->findById($id);

    if (!$url) {
        throw new HttpNotFoundException($request);
    }

    $urlChecks = $this->get(UrlCheckRepository::class)->findByUrlId($url->getId());

    $messages = $this->get('flash')->getMessages();
    $params = [
        'url' => $url,
        'checks' => $urlChecks,
        'flash' => $messages
    ];
    $twig = $this->get(Twig::class);

    return $twig->render($response, 'urls/show.html.twig', $params);
})->setName('urls.show');

$app->post('/urls/{url_id}/checks', function ($request, $response, $args) use ($router) {
    $urlId = (int) $args['url_id'];
    $url = $this->get(UrlRepository::class)->findById($urlId);

    if (!$url) {
        throw new HttpNotFoundException($request);
    }

    $urlCheck = new UrlCheck($url->getId());
    $client = new Client();

    try {
        $res = $client->request('GET', $url->getName());
        $statusCode = $res->getStatusCode();

        $document = new Document($url->getName(), true);

        /** @phpstan-ignore method.notFound */
        $h1 = trim(optional($document->first('h1'))->text());

        /** @phpstan-ignore method.notFound */
        $title = trim(optional($document->first('title'))->text());

        /** @phpstan-ignore method.notFound */
        $description = trim(optional($document->first('meta[name="description"]'))->getAttribute('content'));

        $urlCheck->setStatusCode($statusCode);
        $urlCheck->setH1($h1);
        $urlCheck->setTitle($title);
        $urlCheck->setDescription($description);

        $this->get(UrlCheckRepository::class)->create($urlCheck);

        $this->get('flash')->addMessage('success', 'Страница успешно проверена');
    } catch (RequestException $e) {
        $statusCode = $e->getCode();
        $reason = $e->getResponse()->getReasonPhrase();
        $text = "$statusCode $reason";

        $urlCheck->setStatusCode($statusCode);
        $urlCheck->setH1($text);
        $urlCheck->setTitle($text);

        $this->get(UrlCheckRepository::class)->create($urlCheck);

        $this->get('flash')->addMessage('warning', 'Проверка была выполнена успешно, но сервер ответил с ошибкой');
    } catch (ConnectException) {
        $this->get('flash')->addMessage('danger', 'Произошла ошибка при проверке, не удалось подключиться');
    }

    return $response->withRedirect($router->urlFor('urls.show', ['id' => $url->getId()]), 301);
})->setName('checks.store');

$app->run();
