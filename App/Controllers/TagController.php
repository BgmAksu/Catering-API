<?php

namespace App\Controllers;

use App\DTO\TagDTO;
use App\Helper\Request;
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
        $limit = Request::limitDecider();
        $cursor = Request::cursorDecider();

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
        $data = Request::getJsonData();
        $dto = new TagDTO($data);

        if (!$dto->isValid()) {
            throw new BadRequest(['error' => 'Tag name is required']);
        }
        if ($this->tagRepo->existsByName($dto->name)) {
            throw new BadRequest(['error' => 'Tag name must be unique']);
        }

        $id = $this->tagRepo->create($dto->name);
        (new Created(['id' => $id]))->send();
    }

    /**
     * Update a tag's name by ID.
     * Body: { "name": "New Name" }
     */
    public function update($id)
    {
        $data = Request::getJsonData();
        $dto = new TagDTO($data);

        if (!$dto->isValid()) {
            throw new BadRequest(['error' => 'Tag name is required']);
        }
        if (!$this->tagRepo->getById($id)) {
            throw new NotFound(['error' => 'Tag not found']);
        }
        if ($this->tagRepo->existsByName($dto->name, $id)) {
            throw new BadRequest(['error' => 'Tag name must be unique']);
        }
        $this->tagRepo->update($id, $dto->name);
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
