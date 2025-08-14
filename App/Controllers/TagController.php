<?php

namespace App\Controllers;

use App\DTO\TagDTO;
use App\Helper\Cursor;
use App\Helper\Request;
use App\Middleware\Authenticate;
use App\Models\Tag;
use App\Plugins\Di\Injectable;
use App\Plugins\Http\Exceptions\UnprocessableEntity;
use App\Plugins\Http\Response\Ok;
use App\Plugins\Http\Response\Created;
use App\Plugins\Http\Response\NoContent;
use App\Plugins\Http\Exceptions\BadRequest;
use App\Plugins\Http\Exceptions\NotFound;
use App\Repositories\TagRepository;

class TagController extends Injectable
{
    /**
     * @var
     */
    protected $pdo;

    /**
     * @var TagRepository
     */
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

        [$models, $nextCursor] = $this->tagRepo->getPaginatedModels($limit, $cursor);
        $tags = array_map(fn(Tag $t) => $t->toArray(), $models);

        (new Ok([
            'limit'       => $limit,
            'cursor'      => isset($_GET['cursor']) ? (string)$_GET['cursor'] : '0',
            'next_cursor' => Cursor::encodeOrNull($nextCursor),
            'tags'        => $tags,
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
        $row = $this->tagRepo->getById((int)$id);
        if (!$row) {
            throw new NotFound(['error' => 'Tag not found']);
        }
        $tag = Tag::fromArray($row);
        (new Ok($tag->toArray()))->send();
    }

    /**
     * Create a new tag.
     * POST /api/tags
     * Body: { "name": "Gluten Free" }
     * @return void
     * @throws UnprocessableEntity
     * @throws BadRequest
     */
    public function create(): void
    {
        $data = Request::getJsonData();
        $dto  = new TagDTO(is_array($data) ? $data : [], false); // create mode

        if (!$dto->isValid()) {
            throw new UnprocessableEntity([
                'message' => 'Validation failed',
                'errors'  => $dto->errors(),
            ]);
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
     * @throws UnprocessableEntity
     */
    public function update($id): void
    {
        $data = Request::getJsonData();
        $dto  = new TagDTO(is_array($data) ? $data : [], true); // update mode (partial)

        if (!$dto->isValid()) {
            throw new UnprocessableEntity([
                'message' => 'Validation failed',
                'errors'  => $dto->errors(),
            ]);
        }

        $existing = $this->tagRepo->getById((int)$id);
        if (!$existing) {
            throw new NotFound(['error' => 'Tag not found']);
        }

        $patch = $dto->toPatchArray();

        if (isset($patch['name'])) {
            $current = (string)$existing['name'];
            $new     = (string)$patch['name'];

            // Case-insensitive equality means "no-op" unless it's a case-only change
            if (mb_strtolower($current) === mb_strtolower($new)) {
                if ($current === $new) {
                    // Exact same string -> no-op (idempotent PUT)
                    (new Ok(['message' => 'No changes', 'updated' => false]))->send();
                    return;
                }
                // Only case changed (e.g., 'vegan' -> 'Vegan'): allow update
            }

            $updated = $this->tagRepo->updateIfNameUnique((int)$id, $new);
            if ($updated === false) {
                throw new BadRequest(['error' => 'Tag name must be unique']);
            }

            (new Ok(['message' => 'Tag updated', 'updated' => true]))->send();
            return;
        }

        // Nothing to update (empty patch)
        (new Ok(['message' => 'No changes', 'updated' => false]))->send();
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
        $deleted = $this->tagRepo->delete((int)$id);
        if (!$deleted) {
            throw new NotFound(['error' => 'Tag not found or used by a facility']);
        }
        (new NoContent())->send();
    }
}
