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
            $url = $this->hydrate($row);
            $urls[] = $url;
        }

        return $urls;
    }

    public function urlsWithLastCheck(): array
    {
        $sql = "SELECT
            urls.id,
            urls.name,
            urls.created_at AS created_at,
            MAX(url_checks.created_at) AS check_created_at,
            url_checks.status_code AS check_status_code
        FROM urls
        LEFT JOIN url_checks ON
            urls.id = url_checks.url_id
        GROUP BY urls.id, url_checks.status_code
        ORDER BY urls.id
        ";

        $stmt = $this->conn->query($sql);

        $urlChecks = [];
        while ($row = $stmt->fetch()) {
            $urlChecks[] = $row;
        }

        return $urlChecks;
    }

    public function findById(int $id): ?Url
    {
        $sql = "SELECT * FROM urls WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id]);

        if ($row = $stmt->fetch()) {
            return $this->hydrate($row);
        }

        return null;
    }

    public function findByName(string $name): ?Url
    {
        $sql = "SELECT * FROM urls WHERE name = :name";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$name]);

        if ($row = $stmt->fetch()) {
            return $this->hydrate($row);
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

    private function hydrate(array $row): Url
    {
        $url = new Url($row['name'], $row['created_at']);
        $url->setId($row['id']);

        return $url;
    }
}
