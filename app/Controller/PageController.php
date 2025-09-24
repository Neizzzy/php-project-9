<?php

namespace Hexlet\Code\Controller;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Slim\Http\ServerRequest as Request;
use Slim\Http\Response;
use Slim\Views\Twig;

class PageController extends Controller
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function home(Request $request, Response $response): Response
    {
        $twig = $this->container->get(Twig::class);
        return $twig->render($response, 'home.html.twig');
    }
}
