<?php

namespace Hexlet\Code\Controller;

use Hexlet\Code\Model\Url;
use Hexlet\Code\Repository\UrlCheckRepository;
use Hexlet\Code\Repository\UrlRepository;
use Monolog\Logger;
use Slim\Exception\HttpNotFoundException;
use Slim\Flash\Messages;
use Slim\Interfaces\RouteParserInterface;
use Slim\Views\Twig;
use Slim\Http\ServerRequest as Request;
use Slim\Http\Response;
use Psr\Http\Message\ResponseInterface;

class UrlController extends Controller
{
    public function __construct(
        Messages $flash,
        RouteParserInterface $router,
        Logger $logger,
        Twig $twig,
        private readonly UrlRepository $urlRepository,
        private readonly UrlCheckRepository $urlCheckRepository
    ) {
        parent::__construct($flash, $router, $logger, $twig);
    }


    public function index(Request $request, Response $response): ResponseInterface
    {
        $urls = $this->urlRepository->urlsWithLastCheck();

        $params = ['urls' => $urls];

        return $this->render($response, 'urls/index.html.twig', $params);
    }

    public function store(Request $request, Response $response): ResponseInterface
    {
        $urlData = $request->getParsedBodyParam('url', '');

        $validator = new \Valitron\Validator($urlData);
        $validator->rule('required', 'name')->message('URL не должен быть пустым');
        $validator->rule('url', 'name')->message('Некорректный URL');
        $validator->rule('lengthMax', 'name', 255)->message('Слишком длинный URL');

        if ($validator->validate()) {
            $normalizedUrl = parse_url($urlData['name'], PHP_URL_SCHEME) . '://' .
                parse_url($urlData['name'], PHP_URL_HOST);

            $sameName = $this->urlRepository->findByName($normalizedUrl);
            if ($sameName) {
                $this->flash->addMessage('success', 'Страница уже существует');
                return $response->withRedirect(
                    $this->router->urlFor('urls.show', ['id' => (string) $sameName->getId()]),
                    301
                );
            }

            $url = new Url($normalizedUrl);
            $this->urlRepository->create($url);

            $this->flash->addMessage('success', 'Страница успешно добавлена');

            return $response->withRedirect(
                $this->router->urlFor('urls.show', ['id' => (string) $url->getId()]),
                301
            );
        }

        $params = [
            'errors' => $validator->errors(),
            'url' => $urlData
        ];

        return $this->render($response, 'home.html.twig', $params)->withStatus(422);
    }

    /**
     * @param array<string, mixed> $args
     */
    public function show(Request $request, Response $response, array $args): ResponseInterface
    {
        $id = (int) $args['id'];
        $url = $this->urlRepository->findById($id);

        if (!$url) {
            throw new HttpNotFoundException($request);
        }

        $urlChecks = $this->urlCheckRepository->findByUrlId($id);

        $messages = $this->flash->getMessages();
        $params = [
            'url' => $url,
            'checks' => $urlChecks,
            'flash' => $messages
        ];

        return $this->render($response, 'urls/show.html.twig', $params);
    }
}
