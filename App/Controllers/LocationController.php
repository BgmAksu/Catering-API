<?php

namespace App\Controllers;

use App\DTO\LocationDTO;
use App\Helper\Request;
use App\Middleware\Authenticate;
use App\Plugins\Di\Injectable;
use App\Plugins\Http\Response\Ok;
use App\Plugins\Http\Response\Created;
use App\Plugins\Http\Response\NoContent;
use App\Plugins\Http\Exceptions\BadRequest;
use App\Plugins\Http\Exceptions\NotFound;
use App\Repositories\LocationRepository;

class LocationController extends Injectable
{
    protected $pdo;
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

        $locations = $this->locationRepo->getPaginated($limit, $cursor);
        $maxId = count($locations) ? end($locations)['id'] : $cursor;
        $nextCursor = count($locations) ? $maxId : null;

        (new Ok([
            'limit' => $limit,
            'cursor' => $cursor,
            'next_cursor' => $nextCursor,
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
        $location = $this->locationRepo->getById($id);
        if (!$location) {
            throw new NotFound(['error' => 'Location not found']);
        }
        (new Ok($location))->send();
    }

    /**
     * Create a new location.
     * POST /api/locations
     * Body: { "city": "...", "address": "...", "zip_code": "...", "country_code": "...", "phone_number": "..." }
     * @return void
     * @throws BadRequest
     */
    public function create(): void
    {
        $data = Request::getJsonData();
        $dto = new LocationDTO($data);

        if (!$dto->isValid()) {
            throw new BadRequest(['error' => 'Invalid input']);
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
     * @throws BadRequest
     * @throws NotFound
     */
    public function update($id): void
    {
        $data = Request::getJsonData();
        $dto = new LocationDTO($data);

        if (!$dto->isValid()) {
            throw new BadRequest(['error' => 'Invalid input']);
        }

        $updated = $this->locationRepo->update($id, $dto->asArray());
        if (!$updated) {
            throw new NotFound(['error' => 'Location not found']);
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
        $deleted = $this->locationRepo->delete($id);
        if (!$deleted) {
            throw new NotFound(['error' => 'Location not found']);
        }
        (new NoContent())->send();
    }
}
