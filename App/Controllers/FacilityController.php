<?php

namespace App\Controllers;

use App\DTO\FacilityDTO;
use App\DTO\TagDTO;
use App\Helper\Request;
use App\Helper\Sanitizer;
use App\Plugins\Di\Injectable;
use App\Plugins\Http\Response\Ok;
use App\Plugins\Http\Response\Created;
use App\Plugins\Http\Response\NoContent;
use App\Plugins\Http\Exceptions\BadRequest;
use App\Plugins\Http\Exceptions\NotFound;
use App\Repositories\FacilityRepository;
use App\Repositories\EmployeeRepository;
use App\Repositories\TagRepository;
use App\Repositories\LocationRepository;

class FacilityController extends Injectable
{
    protected $pdo;
    protected FacilityRepository $facilityRepo;
    protected EmployeeRepository $employeeRepo;
    protected TagRepository $tagRepo;
    protected LocationRepository $locationRepo;

    public function __construct()
    {
        $this->pdo = $this->db->getConnection();
        $this->facilityRepo = new FacilityRepository($this->pdo);
        $this->employeeRepo = new EmployeeRepository($this->pdo);
        $this->tagRepo = new TagRepository($this->pdo);
        $this->locationRepo = new LocationRepository($this->pdo);
    }

    /**
     * List all facilities with cursor-based pagination, including location, tags, and employees.
     * GET /api/facilities?limit=10&cursor=0
     * @return void
     */
    public function list(): void
    {
        $limit = Request::limitDecider();
        $cursor = Request::cursorDecider();

        list($facilities, $maxId) = $this->facilityRepo->getPaginated($limit, $cursor);
        foreach ($facilities as $fid => &$fac) {
            $fac['employees'] = $this->employeeRepo->getByFacility($fid);
        }
        $nextCursor = count($facilities) ? $maxId : null;

        (new Ok([
            'limit' => $limit,
            'cursor' => $cursor,
            'next_cursor' => $nextCursor,
            'facilities' => array_values($facilities)
        ]))->send();
    }

    /**
     * Search facilities with cursor-based pagination.
     * GET /api/facilities/search?name=...&tag=...&city=...&limit=10&cursor=0
     * @return void
     */
    public function search(): void
    {
        $filters = [
            'name' => isset($_GET['name']) ? Sanitizer::string($_GET['name']) : null,
            'tag' => isset($_GET['tag']) ? Sanitizer::string($_GET['tag']) : null,
            'city' => isset($_GET['city']) ? Sanitizer::string($_GET['city']) : null,
        ];

        $limit = Request::limitDecider();
        $cursor = Request::cursorDecider();

        list($facilities, $maxId) = $this->facilityRepo->getPaginated($limit, $cursor, $filters);
        foreach ($facilities as $fid => &$fac) {
            $fac['employees'] = $this->employeeRepo->getByFacility($fid);
        }
        $nextCursor = count($facilities) ? $maxId : null;

        (new Ok([
            'limit' => $limit,
            'cursor' => $cursor,
            'next_cursor' => $nextCursor,
            'facilities' => array_values($facilities)
        ]))->send();
    }

    /**
     * Get detail of a facility with location, tags, and employees.
     * @param $id
     * @return void
     * @throws NotFound
     */
    public function detail($id): void
    {
        $facility = $this->facilityRepo->getById($id);
        if (!$facility) {
            throw new NotFound(['error' => 'Facility not found']);
        }
        $facility['employees'] = $this->employeeRepo->getByFacility($id);

        (new Ok($facility))->send();
    }

    /**
     * Create a new facility.
     * @throws BadRequest
     */
    public function create(): void
    {
        $data = Request::getJsonData();
        $dto = new FacilityDTO($data);

        if (!$dto->isValid()) {
            throw new BadRequest(['error' => 'Invalid input']);
        }

        $locationId = $this->locationRepo->create($dto->location);
        $facilityId = $this->facilityRepo->create($dto->name, $locationId);

        foreach ($dto->tags as $tagName) {
            if ($tagName === '') continue;
            $tagId = $this->tagRepo->createIfNotExists($tagName);
            $this->tagRepo->addTagToFacility($facilityId, $tagId);
        }

        (new Created(['id' => $facilityId]))->send();
    }

    /**
     * Update a facility.
     * @param $id
     * @return void
     * @throws BadRequest
     * @throws NotFound
     */
    public function update($id): void
    {
        $data = Request::getJsonData();
        $dto = new FacilityDTO($data);

        if (!$dto->isValid()) {
            throw new BadRequest(['error' => 'Invalid input']);
        }

        // Find existing facility and its location
        $facility = $this->facilityRepo->getById($id);
        if (!$facility) {
            throw new NotFound(['error' => 'Facility not found']);
        }

        $locationId = $this->facilityRepo->getLocationId($id);
        if (!$locationId) {
            throw new NotFound(['error' => 'Location not found']);
        }

        $this->locationRepo->update($locationId, $dto->location);
        $this->facilityRepo->update($id, $dto->name);

        (new Ok(['message' => 'Facility updated']))->send();
    }

    /**
     * Delete a facility.
     * @param $id
     * @return void
     * @throws NotFound
     */
    public function delete($id): void
    {
        $deleted = $this->facilityRepo->delete($id);
        if (!$deleted) {
            throw new NotFound(['error' => 'Facility not found']);
        }
        (new NoContent())->send();
    }

    /**
     * Add tag(s) to facility.
     * @param $facilityId
     * @return void
     * @throws NotFound
     */
    public function addTags($facilityId): void
    {
        $facility = $this->facilityRepo->getById($facilityId);
        if (!$facility) {
            throw new NotFound(['error' => 'Facility not found']);
        }

        $data = Request::getJsonData();
        $tags = is_array($data) ? $data : [];
        $added = [];

        foreach ($tags as $tagName) {
            $tagDTO = new TagDTO($tagName);
            if ($tagDTO->isValid()) {
                $tagId = $this->tagRepo->createIfNotExists($tagDTO->name);
                // If no, then add
                if (!$this->tagRepo->facilityHasTag($facilityId, $tagId)) {
                    $this->tagRepo->addTagToFacility($facilityId, $tagId);
                    $added[] = $tagDTO->name;
                }
            }
        }

        (new Ok(['added' => $added]))->send();
    }

    /**
     * Remove tag(s) from facility
     * @param $facilityId
     * @return void
     * @throws NotFound
     */
    public function removeTags($facilityId): void
    {
        $facility = $this->facilityRepo->getById($facilityId);
        if (!$facility) {
            throw new NotFound(['error' => 'Facility not found']);
        }

        $data = Request::getJsonData();
        $tags = is_array($data) ? $data : [];
        $removed = [];

        foreach ($tags as $tagName) {
            $tagDTO = new TagDTO($tagName);
            if ($tagDTO->isValid()) {
                $tagId = $this->tagRepo->findIdByName($tagDTO->name);
                if ($tagId && $this->tagRepo->facilityHasTag($facilityId, $tagId)) {
                    $this->tagRepo->removeFacilityTag($facilityId, $tagId);
                    $removed[] = $tagDTO->name;
                }
            }
        }

        (new Ok(['removed' => $removed]))->send();
    }
}
