<?php

use Carbon\Carbon;
use DI\Container;
use Hexlet\Code\Model\Url;
use Hexlet\Code\Repository\UrlRepository;
use Slim\Factory\AppFactory;
use Slim\Flash\Messages;
use Slim\Middleware\MethodOverrideMiddleware;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;

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
        die("Database Error: " . $e->getMessage());
    } catch (RuntimeException $e) {
        die("Runtime error: " . $e->getMessage());
    }
});

$app = AppFactory::createFromContainer($container);

$app->add(TwigMiddleware::create($app, $container->get(Twig::class)));
$app->addRoutingMiddleware();
$app->addErrorMiddleware(true, true, true);
$app->add(MethodOverrideMiddleware::class);

$router = $app->getRouteCollector()->getRouteParser();

$app->get('/', function ($request, $response) {
    $twig = $this->get(Twig::class);
    return $twig->render($response, 'home.html.twig');
})->setName('home');

$app->get('/urls', function ($request, $response) {
    $urls = $this->get(UrlRepository::class)->all();

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

        $url = new Url();
        $url->setName($normalizedUrl);
        $url->setCreatedAt(Carbon::now());
        $urlRepository->create($url);

        $this->get('flash')->addMessage('success', 'Страница успешно добавлена');

        return $response->withRedirect($router->urlFor('urls.show', ['id' => $url->getId()]), 301);
    }

    $twig = $this->get(Twig::class);
    $params = [
        'errors' => $validator->errors(),
        'url' => $urlData
    ];

    return $twig->render($response, 'home.html.twig', $params);
})->setName('urls.store');

$app->get('/urls/{id}', function ($request, $response, $args) {
    $id = $args['id'];
    $url = $this->get(UrlRepository::class)->findById($id);

    if (!$url) {
        return $response->write('Страница не найдена!')->withStatus(404);
    }

    $messages = $this->get('flash')->getMessages();
    $params = [
        'url' => $url,
        'flash' => $messages
    ];
    $twig = $this->get(Twig::class);

    return $twig->render($response, 'urls/show.html.twig', $params);
})->setName('urls.show');

$app->run();
