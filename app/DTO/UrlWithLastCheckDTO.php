<?php

namespace Hexlet\Code\DTO;

readonly class UrlWithLastCheckDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $checkCreatedAt,
        public ?int $statusCode
    ) {
    }
}
