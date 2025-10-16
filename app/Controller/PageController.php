<?php

namespace Hexlet\Code\Controller;

use Slim\Http\ServerRequest as Request;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;

class PageController extends Controller
{
    public function home(Request $request, Response $response): ResponseInterface
    {
        return $this->render($response, 'home.html.twig');
    }
}
