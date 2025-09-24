<?php

namespace Hexlet\Code\Repository;

use Hexlet\Code\DTO\UrlWithLastCheckDTO;
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

        if ($stmt === false) {
            return [];
        }

        $urls = [];
        while ($row = $stmt->fetch()) {
            $url = $this->hydrate($row);
            $urls[] = $url;
        }

        return $urls;
    }

    public function urlsWithLastCheck(): array
    {
        $sql = "SELECT DISTINCT ON (u.id)
            u.id,
            u.name,
            uc.created_at AS check_created_at,
            uc.status_code AS check_status_code
        FROM urls AS u
        LEFT JOIN url_checks AS uc ON
            u.id = uc.url_id
        ORDER BY u.id ASC, uc.created_at DESC";

        $stmt = $this->conn->query($sql);

        if ($stmt === false) {
            return [];
        }

        $urlChecks = [];
        while ($row = $stmt->fetch()) {
            $urlWithLastCheck = new UrlWithLastCheckDTO(
                $row['id'],
                $row['name'],
                $row['check_created_at'],
                $row['check_status_code']
            );

            $urlChecks[] = $urlWithLastCheck;
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

        $lastInsertId = (int) $this->conn->lastInsertId();
        $url->setId($lastInsertId);
    }

    private function hydrate(array $row): Url
    {
        $url = new Url($row['name'], $row['created_at']);
        $url->setId($row['id']);

        return $url;
    }
}
