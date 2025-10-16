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
use Monolog\Logger;
use Slim\Exception\HttpNotFoundException;
use Slim\Flash\Messages;
use Slim\Http\ServerRequest as Request;
use Slim\Http\Response;
use Psr\Http\Message\ResponseInterface;
use Slim\Interfaces\RouteParserInterface;
use Slim\Views\Twig;

class UrlCheckController extends Controller
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

    /**
     * @param array<string, mixed> $args
     */
    public function store(Request $request, Response $response, array $args): ResponseInterface
    {
        $urlId = (int) $args['url_id'];
        $url = $this->urlRepository->findById($urlId);

        if (!$url) {
            throw new HttpNotFoundException($request);
        }

        $urlCheck = new UrlCheck($urlId);
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

            $this->urlCheckRepository->create($urlCheck);

            $this->flash->addMessage('success', 'Страница успешно проверена');
        } catch (RequestException $e) {
            $statusCode = $e->getCode();
            $exceptionResponse = $e->getResponse();
            $reason = $exceptionResponse !== null ? $exceptionResponse->getReasonPhrase() : '';
            $text = "$statusCode $reason";

            $urlCheck->setStatusCode($statusCode);
            $urlCheck->setH1($text);
            $urlCheck->setTitle($text);

            $this->urlCheckRepository->create($urlCheck);

            $this->flash
                ->addMessage('warning', 'Проверка была выполнена успешно, но сервер ответил с ошибкой');
        } catch (ConnectException) {
            $this->flash
                ->addMessage('danger', 'Произошла ошибка при проверке, не удалось подключиться');
        } catch (GuzzleException $e) {
            $this->logger->error('Guzzle Error', ['exception' => $e]);

            $this->flash
                ->addMessage('danger', 'Произошла ошибка при проверке, не удалось подключиться');
        } catch (InvalidSelectorException $e) {
            $this->logger->error('DiDOM Invalid Selector Error', ['exception' => $e]);

            $this->flash
                ->addMessage('danger', 'Произошла ошибка при анализе HTML страницы');
        }

        return $response->withRedirect(
            $this->router->urlFor('urls.show', ['id' => (string) $url->getId()]),
            301
        );
    }
}
