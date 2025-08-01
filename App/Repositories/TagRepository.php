<?php

namespace App\Repositories;

class TagRepository
{
    protected $pdo;
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function getPaginated($limit, $cursor)
    {
        $sql = "SELECT id, name FROM tags WHERE id > ? ORDER BY id LIMIT ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(1, $cursor, \PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, \PDO::PARAM_INT);
        $stmt->execute();
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

    public function createIfNotExists($tagName)
    {
        if ($this->existsByName($tagName)) {
            return false;
        }
        return $this->create($tagName);
    }

    public function update($id, $name)
    {
        $stmt = $this->pdo->prepare("UPDATE tags SET name = ? WHERE id = ?");
        $stmt->execute([$name, $id]);
        return $stmt->rowCount();
    }

    public function updateIfNameUnique($id, $name)
    {
        if ($this->existsByName($name)) {
            return false;
        }
        return $this->update($id, $name);
    }

    public function delete($id)
    {
        // Check if tag is used by any facility
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM facility_tags WHERE tag_id=?");
        $stmt->execute([$id]);
        $usedCount = $stmt->fetchColumn();

        if ($usedCount > 0) {
            return 0;
        } else {
            $stmt = $this->pdo->prepare("DELETE FROM tags WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->rowCount();
        }
    }

    public function existsByName($name): bool
    {
        $stmt = $this->pdo->prepare("SELECT 1 FROM tags WHERE name = ?");
        $stmt->execute([$name]);
        return (bool)$stmt->fetchColumn();
    }
    public function findIdByName($name)
    {
        $stmt = $this->pdo->prepare("SELECT id FROM tags WHERE name = ?");
        $stmt->execute([$name]);
        return $stmt->fetchColumn();
    }

    public function facilityHasTag($facilityId, $tagId): bool
    {
        $stmt = $this->pdo->prepare("SELECT 1 FROM facility_tags WHERE facility_id=? AND tag_id=?");
        $stmt->execute([$facilityId, $tagId]);
        return (bool)$stmt->fetchColumn();
    }

    public function addTagToFacility($facilityId, $tagId)
    {
        $stmt = $this->pdo->prepare("INSERT INTO facility_tags (facility_id, tag_id) VALUES (?, ?)");
        $stmt->execute([$facilityId, $tagId]);
        return $stmt->rowCount();
    }


    public function removeTagFromFacility($facilityId, $tagId)
    {
        $stmt = $this->pdo->prepare("DELETE FROM facility_tags WHERE facility_id=? AND tag_id=?");
        $stmt->execute([$facilityId, $tagId]);
        return $stmt->rowCount();
    }
}