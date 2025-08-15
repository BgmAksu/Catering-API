<?php

namespace App\Controllers;

use App\DTO\TagDTO;
use App\Enums\ResponseErrors;
use App\Enums\ResponseMessages;
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
        $tag = $this->tagRepo->getByIdModel((int)$id);
        if (!$tag) {
            throw new NotFound(['error' => ResponseErrors::ERROR_TAG_NOT_FOUND->value]);
        }

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
                'message' => ResponseErrors::ERROR_VALIDATION_FAILED->value,
                'errors'  => $dto->errors(),
            ]);
        }

        $created = $this->tagRepo->createIfNotExists($dto->name);
        if ($created === false) {
            throw new BadRequest(['error' => ResponseErrors::ERROR_TAG_MUST_BE_UNIQUE->value]);
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
        $dto  = new TagDTO(is_array($data) ? $data : [], true); // update mode
        if (!$dto->isValid()) {
            throw new UnprocessableEntity([
                'message' => ResponseErrors::ERROR_VALIDATION_FAILED->value,
                'errors'  => $dto->errors(),
            ]);
        }

        $existing = $this->tagRepo->getByIdModel((int)$id);
        if (!$existing) {
            throw new NotFound(['error' => ResponseErrors::ERROR_TAG_NOT_FOUND->value]);
        }

        $patch = $dto->toPatchArray();
        if (isset($patch['name'])) {
            $current = $existing->name;
            $new     = (string)$patch['name'];

            // Case-insensitive equality means "no-op" unless it's a case-only change
            if (mb_strtolower($current) === mb_strtolower($new)) {
                if ($current === $new) {
                    (new Ok(['message' => ResponseMessages::NO_CHANGES->value, 'updated' => false]))->send();
                    return;
                }
            }

            $updated = $this->tagRepo->updateIfNameUnique((int)$id, $new);
            if ($updated === false) {
                throw new BadRequest(['error' => ResponseErrors::ERROR_TAG_MUST_BE_UNIQUE->value]);
            }

            (new Ok(['message' => ResponseMessages::SUCCESS_TAG_UPDATED->value, 'updated' => true]))->send();
            return;
        }

        // Nothing to update (empty patch)
        (new Ok(['message' => ResponseMessages::NO_CHANGES->value, 'updated' => false]))->send();
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
            throw new NotFound(['error' => ResponseErrors::ERROR_TAG_DELETE->value]);
        }

        (new NoContent())->send();
    }
}
