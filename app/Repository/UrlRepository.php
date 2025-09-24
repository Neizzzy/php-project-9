<?php

namespace Hexlet\Code\Repository;

use Hexlet\Code\DTO\UrlWithLastCheckDTO;
use Hexlet\Code\Model\Url;
use Illuminate\Support\Collection;

class UrlRepository
{
    private \PDO $conn;

    public function __construct(\PDO $pdo)
    {
        $this->conn = $pdo;
    }

    /**
     * @return Collection<int, UrlWithLastCheckDTO>
     */
    public function urlsWithLastCheck(): Collection
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
            return collect();
        }

        return collect($stmt->fetchAll())->map(function ($row) {
            return new UrlWithLastCheckDTO(
                $row['id'],
                $row['name'],
                $row['check_created_at'],
                $row['check_status_code']
            );
        });
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

    /**
     * @param array<string, mixed> $row
     */
    private function hydrate(array $row): Url
    {
        $url = new Url($row['name'], $row['created_at']);
        $url->setId($row['id']);

        return $url;
    }
}
