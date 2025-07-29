<?php

namespace App\Repositories;

class TagRepository
{
    protected $pdo;
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function createIfNotExists($tagName)
    {
        $id = $this->findIdByName($tagName);
        if ($id) {
            return $id;
        }
        return $this->create($tagName);
    }

    public function deleteFacilityTags($facilityId)
    {
        $stmt = $this->pdo->prepare("DELETE FROM facility_tags WHERE facility_id=?");
        $stmt->execute([$facilityId]);
    }

    public function addTagToFacility($facilityId, $tagId)
    {
        $stmt = $this->pdo->prepare("INSERT INTO facility_tags (facility_id, tag_id) VALUES (?, ?)");
        $stmt->execute([$facilityId, $tagId]);
    }

    public function getPaginated($limit, $cursor)
    {
        $sql = "SELECT id, name FROM tags WHERE id > ? ORDER BY id LIMIT ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$cursor, $limit]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getById($id)
    {
        $stmt = $this->pdo->prepare("SELECT id, name FROM tags WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function create($name)
    {
        $stmt = $this->pdo->prepare("INSERT INTO tags (name) VALUES (?)");
        $stmt->execute([$name]);
        return $this->pdo->lastInsertId();
    }

    public function update($id, $name)
    {
        $stmt = $this->pdo->prepare("UPDATE tags SET name = ? WHERE id = ?");
        $stmt->execute([$name, $id]);
    }

    public function delete($id)
    {
        $stmt = $this->pdo->prepare("DELETE FROM tags WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->rowCount();
    }

    public function existsByName($name, $excludeId = null)
    {
        if ($excludeId !== null) {
            $stmt = $this->pdo->prepare("SELECT 1 FROM tags WHERE name = ? AND id != ?");
            $stmt->execute([$name, $excludeId]);
        } else {
            $stmt = $this->pdo->prepare("SELECT 1 FROM tags WHERE name = ?");
            $stmt->execute([$name]);
        }
        return (bool)$stmt->fetchColumn();
    }
    public function findIdByName($name)
    {
        $stmt = $this->pdo->prepare("SELECT id FROM tags WHERE name = ?");
        $stmt->execute([$name]);
        return $stmt->fetchColumn();
    }
}