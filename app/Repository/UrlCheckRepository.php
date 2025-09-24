<?php

namespace Hexlet\Code\Repository;

use Hexlet\Code\Model\UrlCheck;
use Illuminate\Support\Collection;

class UrlCheckRepository
{
    private \PDO $conn;

    public function __construct(\PDO $conn)
    {
        $this->conn = $conn;
    }

    /**
     * @return Collection<int, UrlCheck>
     */
    public function findByUrlId(int $urlId): Collection
    {
        $sql = "SELECT * FROM url_checks WHERE url_id = :url_id ORDER BY id DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$urlId]);

        return collect($stmt->fetchAll())->map(function ($row) {
            return $this->hydrate($row);
        });
    }

    public function create(UrlCheck $urlCheck): void
    {
        $sql = "INSERT INTO url_checks
            (url_id, status_code, h1, title, description, created_at)
            VALUES (:url_id, :status_code, :h1, :title, :description, :created_at)
        ";
        $stmt = $this->conn->prepare($sql);

        $urlId = $urlCheck->getUrlId();
        $statusCode = $urlCheck->getStatusCode();
        $h1 = $urlCheck->getH1();
        $title = $urlCheck->getTitle();
        $description = $urlCheck->getDescription();
        $createdAt = $urlCheck->getCreatedAt()->toDateTimeString();

        $stmt->bindParam(':url_id', $urlId);
        $stmt->bindParam(':status_code', $statusCode);
        $stmt->bindParam(':h1', $h1);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':created_at', $createdAt);
        $stmt->execute();

        $lastInsertId = (int) $this->conn->lastInsertId();
        $urlCheck->setId($lastInsertId);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hydrate(array $row): UrlCheck
    {
        $urlCheck = new UrlCheck($row['url_id'], $row['created_at']);
        $urlCheck->setId($row['id']);
        $urlCheck->setStatusCode($row['status_code']);
        $urlCheck->setH1($row['h1']);
        $urlCheck->setTitle($row['title']);
        $urlCheck->setDescription($row['description']);

        return $urlCheck;
    }
}
