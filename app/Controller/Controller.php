<?php

namespace Hexlet\Code\Controller;

use Psr\Container\ContainerInterface;

class Controller
{
    public function __construct(
        protected ContainerInterface $container
    ) {
    }
}
