<?php

namespace App\Repositories;

use App\Models\Tag;

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
     * Cursor pagination (limit+1) with id >= cursor.
     * Returns rows and numeric next cursor.
     *
     * @return array{0: array<int,array{id:int,name:string}>, 1: int|null}
     */
    public function getPaginated(int $limit, int $cursor = 0): mixed
    {
        $limitPlusOne = $limit + 1;

        $sql = "SELECT id, name
            FROM tags
            WHERE id >= :cursor
            ORDER BY id ASC
            LIMIT :limit_plus_one";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':cursor', $cursor, \PDO::PARAM_INT);
        $stmt->bindValue(':limit_plus_one', $limitPlusOne, \PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $hasMore = count($rows) > $limit;
        $nextCursor = null;
        if ($hasMore) {
            $nextCursor = (int)$rows[$limit]['id']; // first id of next page
            $rows = array_slice($rows, 0, $limit);
        }

        return [$rows, $nextCursor];
    }

    /**
     * Typed variant that returns Tag models with next cursor.
     *
     * @return array{0: Tag[], 1: int|null}
     */
    public function getPaginatedModels(int $limit, int $cursor = 0): array
    {
        [$rows, $next] = $this->getPaginated($limit, $cursor);
        $models = [];
        foreach ($rows as $r) {
            $models[] = Tag::fromArray($r);
        }
        return [$models, $next];
    }

    /**
     * @param int $id
     * @return mixed
     */
    public function getById(int $id): mixed
    {
        $stmt = $this->pdo->prepare("SELECT id, name FROM tags WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function create(string $name): mixed
    {
        $stmt = $this->pdo->prepare("INSERT INTO tags (name) VALUES (?)");
        $stmt->execute([$name]);
        return $this->pdo->lastInsertId();
    }

    /**
     * @param string $tagName
     * @return false|mixed
     */
    public function createIfNotExists(string $tagName): mixed
    {
        if ($this->existsByName($tagName)) {
            return false;
        }
        return $this->create($tagName);
    }

    /**
     * @param int $id
     * @param string $name
     * @return mixed
     */
    public function update(int $id, string $name): mixed
    {
        $stmt = $this->pdo->prepare("UPDATE tags SET name = ? WHERE id = ?");
        $stmt->execute([$name, $id]);
        return $stmt->rowCount();
    }

    /**
     * @param int $id
     * @param string $name
     * @return false|mixed
     */
    public function updateIfNameUnique(int $id, string $name): mixed
    {
        if ($this->existsByName($name, $id)) {
            return false;
        }
        return $this->update($id, $name);
    }

    /**
     * @param int $id
     * @return int
     */
    public function delete(int $id): int
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
     * Check if a tag name already exists.
     * Optionally exclude a given tag id (useful for updates).
     *
     * @param string   $name
     * @param int|null $exceptId When provided, ignores this id (self)
     * @return bool
     */
    public function existsByName(string $name, ?int $exceptId = null): bool
    {
        if ($exceptId !== null) {
            $stmt = $this->pdo->prepare(
                "SELECT 1
                 FROM tags
                 WHERE LOWER(name) = LOWER(?)
                 AND id <> ?
                 LIMIT 1"
            );
            $stmt->execute([$name, $exceptId]);
        } else {
            $stmt = $this->pdo->prepare(
                "SELECT 1
                 FROM tags
                 WHERE LOWER(name) = LOWER(?)"
            );
            $stmt->execute([$name]);
        }
        return (bool)$stmt->fetchColumn();
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function findIdByName(string $name): mixed
    {
        $stmt = $this->pdo->prepareprepare(
            "SELECT id
             FROM tags
             WHERE LOWER(name) = LOWER(?)
             ORDER BY id"
        );
        $stmt->execute([$name]);
        return $stmt->fetchColumn();
    }

    /**
     * @param int $facilityId
     * @param int $tagId
     * @return bool
     */
    public function facilityHasTag(int $facilityId, int $tagId): bool
    {
        $stmt = $this->pdo->prepare("SELECT 1 FROM facility_tags WHERE facility_id=? AND tag_id=?");
        $stmt->execute([$facilityId, $tagId]);
        return (bool)$stmt->fetchColumn();
    }

    /**
     * @param int $facilityId
     * @param int $tagId
     * @return mixed
     */
    public function addTagToFacility(int $facilityId, int $tagId): mixed
    {
        $stmt = $this->pdo->prepare("INSERT INTO facility_tags (facility_id, tag_id) VALUES (?, ?)");
        $stmt->execute([$facilityId, $tagId]);
        return $stmt->rowCount();
    }


    /**
     * @param int $facilityId
     * @param int $tagId
     * @return mixed
     */
    public function removeTagFromFacility(int $facilityId, int $tagId): mixed
    {
        $stmt = $this->pdo->prepare("DELETE FROM facility_tags WHERE facility_id=? AND tag_id=?");
        $stmt->execute([$facilityId, $tagId]);
        return $stmt->rowCount();
    }
}