<?php

namespace App\Controllers;

use App\Helper\Sanitizer;
use App\Plugins\Di\Injectable;
use App\Plugins\Http\Response\Ok;
use App\Plugins\Http\Response\Created;
use App\Plugins\Http\Response\NoContent;
use App\Plugins\Http\Exceptions\BadRequest;
use App\Plugins\Http\Exceptions\NotFound;
use App\Repositories\TagRepository;

class TagController extends Injectable
{
    protected $pdo;
    protected $tagRepo;
    public function __construct()
    {
        $this->pdo = $this->db->getConnection();
        $this->tagRepo = new TagRepository($this->pdo);
    }

    /**
     * List all tags (with optional pagination, cursor-based).
     * Usage: /api/tags?limit=20&cursor=0
     */
    public function list()
    {
        $limit = isset($_GET['limit']) && is_numeric($_GET['limit']) && $_GET['limit'] > 0 ? (int)$_GET['limit'] : 20;
        $cursor = isset($_GET['cursor']) && is_numeric($_GET['cursor']) ? (int)$_GET['cursor'] : 0;

        $tags = $this->tagRepo->getPaginated($limit, $cursor);
        $maxId = count($tags) ? end($tags)['id'] : $cursor;
        $nextCursor = count($tags) ? $maxId : null;

        (new Ok([
            'limit' => $limit,
            'cursor' => $cursor,
            'next_cursor' => $nextCursor,
            'tags' => $tags
        ]))->send();
    }

    /**
     * Get details of a single tag by ID.
     */
    public function detail($id)
    {
        $tag = $this->tagRepo->getById($id);
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
        $data = json_decode(file_get_contents('php://input'), true);
        $name = Sanitizer::string($data['name'] ?? '');

        if (empty($name)) {
            throw new BadRequest(['error' => 'Tag name is required']);
        }
        if ($this->tagRepo->existsByName($name)) {
            throw new BadRequest(['error' => 'Tag name must be unique']);
        }

        $id = $this->tagRepo->create($name);
        (new Created(['id' => $id]))->send();
    }

    /**
     * Update a tag's name by ID.
     * Body: { "name": "New Name" }
     */
    public function update($id)
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $name = Sanitizer::string($data['name'] ?? '');

        if (empty($name)) {
            throw new BadRequest(['error' => 'Tag name is required']);
        }
        if (!$this->tagRepo->getById($id)) {
            throw new NotFound(['error' => 'Tag not found']);
        }
        if ($this->tagRepo->existsByName($name, $id)) {
            throw new BadRequest(['error' => 'Tag name must be unique']);
        }
        $this->tagRepo->update($id, $name);
        (new Ok(['message' => 'Tag updated']))->send();
    }

    /**
     * Delete a tag by ID.
     */
    public function delete($id)
    {
        $deleted = $this->tagRepo->delete($id);
        if (!$deleted) {
            throw new NotFound(['error' => 'Tag not found']);
        }
        (new NoContent())->send();
    }
}
