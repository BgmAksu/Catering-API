<?php

namespace App\Controllers;

use App\DTO\LocationDTO;
use App\Helper\Request;
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
    protected $locationRepo;
    public function __construct()
    {
        $this->pdo = $this->db->getConnection();
        $this->locationRepo = new LocationRepository($this->pdo);
    }

    /**
     * List locations with cursor-based pagination.
     * GET /api/locations?limit=20&cursor=0
     */
    public function list()
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
     * Get a single location detail by id.
     */
    public function detail($id)
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
     */
    public function create()
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
     * Update a location by id.
     * PUT /api/locations/{id}
     */
    public function update($id)
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
     * Delete a location by id.
     */
    public function delete($id)
    {
        $deleted = $this->locationRepo->delete($id);
        if (!$deleted) {
            throw new NotFound(['error' => 'Location not found']);
        }
        (new NoContent())->send();
    }
}
