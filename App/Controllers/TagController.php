<?php

namespace App\Controllers;

use App\DTO\TagDTO;
use App\Helper\Request;
use App\Middleware\Authenticate;
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
    protected TagRepository $tagRepo;

    public function __construct()
    {
        Authenticate::check();
        $this->pdo = $this->db->getConnection();
        $this->tagRepo = new TagRepository($this->pdo);
    }

    /**
     * List all tags with cursor-based pagination.
     * GET /api/tags?limit=20&cursor=0
     * @return void
     */
    public function list(): void
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
     * GET /api/tags/{tag_id}
     * @param $id
     * @return void
     * @throws NotFound
     */
    public function detail($id): void
    {
        $tag = $this->tagRepo->getById($id);
        if (!$tag) {
            throw new NotFound(['error' => 'Tag not found']);
        }
        (new Ok($tag))->send();
    }

    /**
     * Create a new tag.
     * POST /api/tags
     * Body: { "name": "Gluten Free" }
     * @return void
     * @throws BadRequest
     */
    public function create(): void
    {
        $data = Request::getJsonData();
        $dto = new TagDTO($data);

        if (!$dto->isValid()) {
            throw new BadRequest(['error' => 'Tag name is required']);
        }

        $created = $this->tagRepo->createIfNotExists($dto->name);
        if ($created === false) {
            throw new BadRequest(['error' => 'Tag name must be unique']);
        }
        (new Created(['id' => $created]))->send();
    }

    /**
     * Update a tag's name by ID.
     * PUT /api/tags/{tag_id}
     * Body: { "name": "New Name" }
     * @param $id
     * @return void
     * @throws BadRequest
     * @throws NotFound
     */
    public function update($id): void
    {
        $data = Request::getJsonData();
        $dto = new TagDTO($data);

        if (!$dto->isValid()) {
            throw new BadRequest(['error' => 'Tag name is required']);
        }
        if (!$this->tagRepo->getById($id)) {
            throw new NotFound(['error' => 'Tag not found']);
        }
        $updated = $this->tagRepo->updateIfNameUnique($id, $dto->name);
        if ($updated === false) {
            throw new BadRequest(['error' => 'Tag name must be unique']);
        }
        (new Ok(['message' => 'Tag updated']))->send();
    }

    /**
     * Delete a tag by ID.
     * DELETE /api/tags/{tag_id}
     * @param $id
     * @return void
     * @throws NotFound
     */
    public function delete($id): void
    {
        $deleted = $this->tagRepo->delete($id);
        if (!$deleted) {
            throw new NotFound(['error' => 'Tag not found']);
        }
        (new NoContent())->send();
    }
}
