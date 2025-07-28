<?php

namespace App\Controllers;

use App\Plugins\Di\Injectable;
use App\Plugins\Http\Response\Ok;
use App\Plugins\Http\Response\Created;
use App\Plugins\Http\Response\NoContent;
use App\Plugins\Http\Exceptions\BadRequest;
use App\Plugins\Http\Exceptions\NotFound;

class TagController extends Injectable
{
    /**
     * Sanitize string input from the client.
     */
    private function sanitizeString($input)
    {
        return trim(strip_tags((string)$input));
    }

    /**
     * List all tags (with optional pagination, cursor-based).
     * Usage: /api/tags?limit=20&cursor=0
     */
    public function list()
    {
        $pdo = $this->db->getConnection();

        $limit = isset($_GET['limit']) && is_numeric($_GET['limit']) && $_GET['limit'] > 0 ? (int)$_GET['limit'] : 20;
        $cursor = isset($_GET['cursor']) && is_numeric($_GET['cursor']) ? (int)$_GET['cursor'] : 0;

        $sql = "SELECT id, name FROM tags WHERE id > :cursor ORDER BY id LIMIT :limit";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':cursor', $cursor, \PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();

        $tags = [];
        $maxId = $cursor;
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $tags[] = $row;
            if ($row['id'] > $maxId) {
                $maxId = $row['id'];
            }
        }

        $nextCursor = count($tags) ? $maxId : null;

        $result = [
            'limit' => $limit,
            'cursor' => $cursor,
            'next_cursor' => $nextCursor,
            'tags' => $tags
        ];

        (new Ok($result))->send();
    }

    /**
     * Get details of a single tag by ID.
     */
    public function detail($id)
    {
        $pdo = $this->db->getConnection();
        $sql = "SELECT id, name FROM tags WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);
        $tag = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$tag) {
            throw new NotFound(['error' => 'Tag not found']);
        }

        (new Ok($tag))->send();
    }

    /**
     * Create a new tag.
     * Body: { "name": "Gluten Free" }
     */
    public function create()
    {
        $pdo = $this->db->getConnection();
        $data = json_decode(file_get_contents('php://input'), true);

        $name = $this->sanitizeString($data['name'] ?? '');

        if (empty($name)) {
            throw new BadRequest(['error' => 'Tag name is required']);
        }

        // Check uniqueness
        $stmt = $pdo->prepare("SELECT id FROM tags WHERE name = ?");
        $stmt->execute([$name]);
        if ($stmt->fetchColumn()) {
            throw new BadRequest(['error' => 'Tag name must be unique']);
        }

        $stmt = $pdo->prepare("INSERT INTO tags (name) VALUES (?)");
        $stmt->execute([$name]);
        (new Created(['id' => $pdo->lastInsertId()]))->send();
    }

    /**
     * Update a tag's name by ID.
     * Body: { "name": "New Name" }
     */
    public function update($id)
    {
        $pdo = $this->db->getConnection();
        $data = json_decode(file_get_contents('php://input'), true);

        $name = $this->sanitizeString($data['name'] ?? '');

        if (empty($name)) {
            throw new BadRequest(['error' => 'Tag name is required']);
        }

        // Check if tag exists
        $stmt = $pdo->prepare("SELECT id FROM tags WHERE id = ?");
        $stmt->execute([$id]);
        if (!$stmt->fetchColumn()) {
            throw new NotFound(['error' => 'Tag not found']);
        }

        // Check uniqueness
        $stmt = $pdo->prepare("SELECT id FROM tags WHERE name = ? AND id != ?");
        $stmt->execute([$name, $id]);
        if ($stmt->fetchColumn()) {
            throw new BadRequest(['error' => 'Tag name must be unique']);
        }

        $stmt = $pdo->prepare("UPDATE tags SET name = ? WHERE id = ?");
        $stmt->execute([$name, $id]);
        (new Ok(['message' => 'Tag updated']))->send();
    }

    /**
     * Delete a tag by ID.
     */
    public function delete($id)
    {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->prepare("DELETE FROM tags WHERE id = ?");
        $stmt->execute([$id]);
        if ($stmt->rowCount() === 0) {
            throw new NotFound(['error' => 'Tag not found']);
        } else {
            (new NoContent())->send();
        }
    }
}
