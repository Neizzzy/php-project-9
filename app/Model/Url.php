<?php

namespace Hexlet\Code\Model;

use Carbon\Carbon;

class Url
{
    private ?int $id = null;
    private string $name;
    private Carbon $createdAt;

    public function __construct(string $name, ?string $createdAt = null)
    {
        $this->name = $name;

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
}
