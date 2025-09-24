<?php

namespace Hexlet\Code\Model;

use Carbon\Carbon;

class UrlCheck
{
    private ?int $id = null;
    private int $urlId;
    private ?int $statusCode = null;
    private ?string $h1 = null;
    private ?string $title = null;
    private ?string $description = null;
    private Carbon $createdAt;

    public function __construct(int $urlId, ?string $createdAt = null)
    {
        $this->urlId = $urlId;

        if (is_null($createdAt)) {
            $this->createdAt = Carbon::now();
        } else {
            $this->createdAt = Carbon::parse($createdAt);
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUrlId(): int
    {
        return $this->urlId;
    }

    public function getStatusCode(): ?int
    {
        return $this->statusCode;
    }

    public function getH1(): ?string
    {
        return $this->h1;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getCreatedAt(): Carbon
    {
        return $this->createdAt;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function setStatusCode(?int $code): void
    {
        $this->statusCode = $code;
    }

    public function setH1(?string $h1): void
    {
        $this->h1 = $h1;
    }

    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }
}
