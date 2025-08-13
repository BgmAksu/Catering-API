<?php

namespace App\Repositories;

/**
 * Tag related DB operations
 */
class TagRepository
{
    /**
     * @var
     */
    protected $pdo;
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * @param $limit
     * @param $cursor
     * @return mixed
     */
    public function getPaginated($limit, $cursor): mixed
    {
        $sql = "SELECT id, name FROM tags WHERE id > ? ORDER BY id LIMIT ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(1, $cursor, \PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @param $id
     * @return mixed
     */
    public function getById($id): mixed
    {
        $stmt = $this->pdo->prepare("SELECT id, name FROM tags WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * @param $name
     * @return mixed
     */
    public function create($name): mixed
    {
        $stmt = $this->pdo->prepare("INSERT INTO tags (name) VALUES (?)");
        $stmt->execute([$name]);
        return $this->pdo->lastInsertId();
    }

    /**
     * @param $tagName
     * @return false|mixed
     */
    public function createIfNotExists($tagName): mixed
    {
        if ($this->existsByName($tagName)) {
            return false;
        }
        return $this->create($tagName);
    }

    /**
     * @param $id
     * @param $name
     * @return mixed
     */
    public function update($id, $name): mixed
    {
        $stmt = $this->pdo->prepare("UPDATE tags SET name = ? WHERE id = ?");
        $stmt->execute([$name, $id]);
        return $stmt->rowCount();
    }

    /**
     * @param $id
     * @param $name
     * @return false|mixed
     */
    public function updateIfNameUnique($id, $name): mixed
    {
        if ($this->existsByName($name)) {
            return false;
        }
        return $this->update($id, $name);
    }

    /**
     * @param $id
     * @return int
     */
    public function delete($id): int
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

    /**
     * @param $name
     * @return bool
     */
    public function existsByName($name): bool
    {
        $stmt = $this->pdo->prepare("SELECT 1 FROM tags WHERE name = ?");
        $stmt->execute([$name]);
        return (bool)$stmt->fetchColumn();
    }

    /**
     * @param $name
     * @return mixed
     */
    public function findIdByName($name): mixed
    {
        $stmt = $this->pdo->prepare("SELECT id FROM tags WHERE name = ?");
        $stmt->execute([$name]);
        return $stmt->fetchColumn();
    }

    /**
     * @param $facilityId
     * @param $tagId
     * @return bool
     */
    public function facilityHasTag($facilityId, $tagId): bool
    {
        $stmt = $this->pdo->prepare("SELECT 1 FROM facility_tags WHERE facility_id=? AND tag_id=?");
        $stmt->execute([$facilityId, $tagId]);
        return (bool)$stmt->fetchColumn();
    }

    /**
     * @param $facilityId
     * @param $tagId
     * @return mixed
     */
    public function addTagToFacility($facilityId, $tagId): mixed
    {
        $stmt = $this->pdo->prepare("INSERT INTO facility_tags (facility_id, tag_id) VALUES (?, ?)");
        $stmt->execute([$facilityId, $tagId]);
        return $stmt->rowCount();
    }


    /**
     * @param $facilityId
     * @param $tagId
     * @return mixed
     */
    public function removeTagFromFacility($facilityId, $tagId): mixed
    {
        $stmt = $this->pdo->prepare("DELETE FROM facility_tags WHERE facility_id=? AND tag_id=?");
        $stmt->execute([$facilityId, $tagId]);
        return $stmt->rowCount();
    }
}