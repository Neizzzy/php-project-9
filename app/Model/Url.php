<?php

namespace Hexlet\Code\Model;

use Carbon\Carbon;

class Url
{
    private ?int $id = null;
    private ?string $name = null;
    private ?Carbon $createdAt = null;

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCreatedAt(): Carbon
    {
        return $this->createdAt;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function setName(string $url): void
    {
        $this->name = $url;
    }

    public function setCreatedAt(Carbon $createAt): void
    {
        $this->createdAt = $createAt;
    }
}
