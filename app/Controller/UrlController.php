<?php

namespace Hexlet\Code\Controller;

use Hexlet\Code\Model\Url;
use Hexlet\Code\Repository\UrlCheckRepository;
use Hexlet\Code\Repository\UrlRepository;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Slim\Exception\HttpNotFoundException;
use Slim\Views\Twig;
use Slim\Http\ServerRequest as Request;
use Slim\Http\Response;

class UrlController extends Controller
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function index(Request $request, Response $response): Response
    {
        $urls = $this->container->get(UrlRepository::class)->urlsWithLastCheck();

        $params = ['urls' => $urls];
        $twig = $this->container->get(Twig::class);

        return $twig->render($response, 'urls/index.html.twig', $params);
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    public function store(Request $request, Response $response): Response
    {
        $urlData = $request->getParsedBodyParam('url', '');

        $validator = new \Valitron\Validator($urlData);
        $validator->rule('required', 'name')->message('URL не должен быть пустым');
        $validator->rule('url', 'name')->message('Некорректный URL');
        $validator->rule('lengthMax', 'name', 255)->message('Слишком длинный URL');

        if ($validator->validate()) {
            $normalizedUrl = parse_url($urlData['name'], PHP_URL_SCHEME) . '://' .
                parse_url($urlData['name'], PHP_URL_HOST);

            $urlRepository = $this->container->get(UrlRepository::class);

            $sameName = $urlRepository->findByName($normalizedUrl);
            if ($sameName) {
                $this->container->get('flash')->addMessage('success', 'Страница уже существует');
                return $response->withRedirect(
                    $this->container->get('router')->urlFor('urls.show', ['id' => (string) $sameName->getId()]),
                    301
                );
            }

            $url = new Url($normalizedUrl);
            $urlRepository->create($url);

            $this->container->get('flash')->addMessage('success', 'Страница успешно добавлена');

            return $response->withRedirect(
                $this->container->get('router')->urlFor('urls.show', ['id' => (string) $url->getId()]),
                301
            );
        }

        $twig = $this->container->get(Twig::class);
        $params = [
            'errors' => $validator->errors(),
            'url' => $urlData
        ];

        return $twig->render($response, 'home.html.twig', $params)->withStatus(422);
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     * @param array<string, mixed> $args
     */
    public function show(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];
        $url = $this->container->get(UrlRepository::class)->findById($id);

        if (!$url) {
            throw new HttpNotFoundException($request);
        }

        $urlChecks = $this->container->get(UrlCheckRepository::class)->findByUrlId($url->getId());

        $messages = $this->container->get('flash')->getMessages();
        $params = [
            'url' => $url,
            'checks' => $urlChecks,
            'flash' => $messages
        ];
        $twig = $this->container->get(Twig::class);

        return $twig->render($response, 'urls/show.html.twig', $params);
    }
}
