<?php

namespace App\Controllers;

use App\DTO\LocationDTO;
use App\Helper\Cursor;
use App\Helper\Request;
use App\Middleware\Authenticate;
use App\Models\Location;
use App\Plugins\Di\Injectable;
use App\Plugins\Http\Exceptions\UnprocessableEntity;
use App\Plugins\Http\Response\Ok;
use App\Plugins\Http\Response\Created;
use App\Plugins\Http\Response\NoContent;
use App\Plugins\Http\Exceptions\NotFound;
use App\Repositories\LocationRepository;

class LocationController extends Injectable
{
    /**
     * @var
     */
    protected $pdo;

    /**
     * @var LocationRepository
     */
    protected LocationRepository $locationRepo;

    public function __construct()
    {
        Authenticate::check();
        $this->pdo = $this->db->getConnection();
        $this->locationRepo = new LocationRepository($this->pdo);
    }

    /**
     * List all locations with cursor-based pagination.
     * GET /api/locations?limit=20&cursor=0
     * @return void
     */
    public function list(): void
    {
        $limit = Request::limitDecider();
        $cursor = Request::cursorDecider();

        [$models, $nextCursor] = $this->locationRepo->getPaginatedModels($limit, $cursor);
        $locations = array_map(fn(Location $m) => $m->toArray(), $models);

        (new Ok([
            'limit' => $limit,
            'cursor' => isset($_GET['cursor']) ? (string)$_GET['cursor'] : '0',
            'next_cursor' => Cursor::encodeOrNull($nextCursor),
            'locations' => $locations
        ]))->send();
    }

    /**
     * Get a single location detail by ID.
     * GET /api/locations/{location_id}
     * @param $id
     * @return void
     * @throws NotFound
     */
    public function detail($id): void
    {
        $location = $this->locationRepo->getByIdModel((int)$id);
        if (!$location) {
            throw new NotFound(['error' => 'Location not found']);
        }
        (new Ok($location->toArray()))->send();
    }

    /**
     * Create a new location.
     * POST /api/locations
     * Body: { "city": "...", "address": "...", "zip_code": "...", "country_code": "...", "phone_number": "..." }
     * @return void
     * @throws UnprocessableEntity
     */
    public function create(): void
    {
        $data = Request::getJsonData();
        $dto  = new LocationDTO(is_array($data) ? $data : [], false);

        if (!$dto->isValid()) {
            throw new UnprocessableEntity([
                'message' => 'Validation failed',
                'errors'  => $dto->errors(),
            ]);
        }

        $locationId = $this->locationRepo->create($dto->asArray());
        (new Created(['id' => $locationId]))->send();
    }

    /**
     * Update a location by ID.
     * PUT /api/locations/{location_id}
     * Body: { "city": "...", "address": "...", "zip_code": "...", "country_code": "...", "phone_number": "..." }
     * @param $id
     * @return void
     * @throws NotFound
     * @throws UnprocessableEntity
     */
    public function update($id): void
    {
        $data = Request::getJsonData();
        $dto  = new LocationDTO(is_array($data) ? $data : [], true);

        if (!$dto->isValid()) {
            throw new UnprocessableEntity([
                'message' => 'Validation failed',
                'errors'  => $dto->errors(),
            ]);
        }

        $existing = $this->locationRepo->getByIdModel((int)$id);
        if (!$existing) {
            throw new NotFound(['error' => 'Location not found']);
        }

        $patch = $dto->toPatchArray();
        if (!empty($patch)) {
            $this->locationRepo->updatePartial((int)$id, $patch);
        }

        (new Ok(['message' => 'Location updated']))->send();
    }

    /**
     * Delete a location by ID.
     * DELETE /api/locations/{location_id}
     * @param $id
     * @return void
     * @throws NotFound
     */
    public function delete($id): void
    {
        $deleted = $this->locationRepo->delete((int)$id);
        if ($deleted <= 0) {
            throw new NotFound(['error' => 'Location not found or used by a facility']);
        }
        (new NoContent())->send();
    }
}
