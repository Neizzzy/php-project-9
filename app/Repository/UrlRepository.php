<?php

namespace Hexlet\Code\Repository;

use Carbon\Carbon;
use Hexlet\Code\Model\Url;

class UrlRepository
{
    private \PDO $conn;

    public function __construct(\PDO $pdo)
    {
        $this->conn = $pdo;
    }

    public function all(): array
    {
        $sql = "SELECT * FROM urls";
        $stmt = $this->conn->query($sql);

        $urls = [];
        while ($row = $stmt->fetch()) {
            $url = $this->hydrateUrl($row);
            $urls[] = $url;
        }

        return $urls;
    }

    public function findById(int $id): ?Url
    {
        $sql = "SELECT * FROM urls WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id]);

        if ($row = $stmt->fetch()) {
            return $this->hydrateUrl($row);
        }

        return null;
    }

    public function findByName(string $name): ?Url
    {
        $sql = "SELECT * FROM urls WHERE name = :name";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$name]);

        if ($row = $stmt->fetch()) {
            return $this->hydrateUrl($row);
        }

        return null;
    }

    public function create(Url $url): void
    {
        $sql = "INSERT INTO urls (name, created_at) VALUES (:name, :createdAt)";
        $stmt = $this->conn->prepare($sql);
        $name = $url->getName();
        $createdAt = $url->getCreatedAt()->toDateTimeString();
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':createdAt', $createdAt);
        $stmt->execute();

        $url->setId($this->conn->lastInsertId());
    }

    private function hydrateUrl(array $row): Url
    {
        $url = new Url();
        $url->setId($row['id']);
        $url->setName($row['name']);
        $url->setCreatedAt(Carbon::create($row['created_at']));

        return $url;
    }
}
