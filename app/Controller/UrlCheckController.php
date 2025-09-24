<?php

namespace Hexlet\Code\Controller;

use DiDom\Document;
use DiDom\Exceptions\InvalidSelectorException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Hexlet\Code\Model\UrlCheck;
use Hexlet\Code\Repository\UrlCheckRepository;
use Hexlet\Code\Repository\UrlRepository;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Slim\Exception\HttpNotFoundException;
use Slim\Http\ServerRequest as Request;
use Slim\Http\Response;

class UrlCheckController extends Controller
{
    /**
     * @throws ContainerExceptionInterface
     * @throws GuzzleException
     * @throws NotFoundExceptionInterface
     * @throws InvalidSelectorException
     * @param array<string, mixed> $args
     */
    public function store(Request $request, Response $response, array $args): Response
    {
        $urlId = (int) $args['url_id'];
        $url = $this->container->get(UrlRepository::class)->findById($urlId);

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
            $h1 = optional($document->first('h1'))->text();

            /** @phpstan-ignore method.notFound */
            $title = optional($document->first('title'))->text();

            /** @phpstan-ignore method.notFound */
            $description = optional($document->first('meta[name="description"]'))->getAttribute('content');

            $urlCheck->setStatusCode($statusCode);
            $urlCheck->setH1($h1);
            $urlCheck->setTitle($title);
            $urlCheck->setDescription($description);

            $this->container->get(UrlCheckRepository::class)->create($urlCheck);

            $this->container->get('flash')->addMessage('success', 'Страница успешно проверена');
        } catch (RequestException $e) {
            $statusCode = $e->getCode();
            $exceptionResponse = $e->getResponse();
            $reason = $exceptionResponse !== null ? $exceptionResponse->getReasonPhrase() : '';
            $text = "$statusCode $reason";

            $urlCheck->setStatusCode($statusCode);
            $urlCheck->setH1($text);
            $urlCheck->setTitle($text);

            $this->container->get(UrlCheckRepository::class)->create($urlCheck);

            $this->container->get('flash')
                ->addMessage('warning', 'Проверка была выполнена успешно, но сервер ответил с ошибкой');
        } catch (ConnectException) {
            $this->container->get('flash')
                ->addMessage('danger', 'Произошла ошибка при проверке, не удалось подключиться');
        }

        return $response->withRedirect(
            $this->container->get('router')->urlFor('urls.show', ['id' => (string) $url->getId()]),
            301
        );
    }
}
