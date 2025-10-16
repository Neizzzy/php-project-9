<?php

namespace Hexlet\Code\Controller;

use Monolog\Logger;
use Psr\Http\Message\ResponseInterface;
use Slim\Flash\Messages;
use Slim\Http\Response;
use Slim\Interfaces\RouteParserInterface;
use Slim\Views\Twig;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class Controller
{
    public function __construct(
        protected readonly Messages $flash,
        protected readonly RouteParserInterface $router,
        protected readonly Logger $logger,
        private readonly Twig $twig,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function render(Response $response, string $template, array $data = []): ResponseInterface
    {
        try {
            return $this->twig->render($response, $template, $data);
        } catch (LoaderError $e) {
            $this->logger->error('TwigLoader Error', ['exception' => $e]);
        } catch (SyntaxError $e) {
            $this->logger->error('TwigSyntax Error', ['exception' => $e]);
        } catch (RuntimeError $e) {
            $this->logger->error('TwigRuntime Error', ['exception' => $e]);
        }

        return $response->withStatus(500, 'Ошибка рендера страницы (Twig error)');
    }
}
