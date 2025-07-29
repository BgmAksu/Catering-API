<?php

namespace App\Controllers;

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
    protected $facilityRepo;
    protected $employeeRepo;
    protected $tagRepo;
    protected $locationRepo;

    public function __construct()
    {
        $this->pdo = $this->db->getConnection();
        $this->facilityRepo = new FacilityRepository($this->pdo);
        $this->employeeRepo = new EmployeeRepository($this->pdo);
        $this->tagRepo = new TagRepository($this->pdo);
        $this->locationRepo = new LocationRepository($this->pdo);
    }
    /**
     * Sanitize a string value from client input.
     * Removes HTML tags and trims whitespace.
     */
    private function sanitizeString($input)
    {
        return trim(strip_tags((string)$input));
    }

    /**
     * List all facilities with cursor-based pagination, including location, tags, and employees.
     * GET /api/facilities?limit=10&cursor=0
     */
    public function list()
    {
        $limit = isset($_GET['limit']) && is_numeric($_GET['limit']) && $_GET['limit'] > 0 ? (int)$_GET['limit'] : 20;
        $cursor = isset($_GET['cursor']) && is_numeric($_GET['cursor']) ? (int)$_GET['cursor'] : 0;

        list($facilities, $ids, $maxId) = $this->facilityRepo->getPaginated($limit, $cursor);
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
     */
    public function search()
    {
        $filters = [
            'name' => isset($_GET['name']) ? $this->sanitizeString($_GET['name']) : null,
            'tag' => isset($_GET['tag']) ? $this->sanitizeString($_GET['tag']) : null,
            'city' => isset($_GET['city']) ? $this->sanitizeString($_GET['city']) : null,
        ];
        $limit = isset($_GET['limit']) && is_numeric($_GET['limit']) && $_GET['limit'] > 0 ? (int)$_GET['limit'] : 20;
        $cursor = isset($_GET['cursor']) && is_numeric($_GET['cursor']) ? (int)$_GET['cursor'] : 0;

        list($facilities, $ids, $maxId) = $this->facilityRepo->getPaginated($limit, $cursor, $filters);
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
     */
    public function detail($id)
    {
        $facility = $this->facilityRepo->getById($id);
        if (!$facility) {
            throw new NotFound(['error' => 'Facility not found']);
        }
        $facility['employees'] = $this->facilityRepo->getByFacility($id);

        (new Ok($facility))->send();
    }

    /**
     * Create a new facility, location, tags, and employees.
     */
    public function create()
    {
        $data = json_decode(file_get_contents('php://input'), true);

        $name = $this->sanitizeString($data['name'] ?? '');
        $location = array_map([$this, 'sanitizeString'], $data['location'] ?? []);
        $tags = array_map([$this, 'sanitizeString'], isset($data['tags']) && is_array($data['tags']) ? $data['tags'] : []);
        $employees = isset($data['employees']) && is_array($data['employees']) ? $data['employees'] : [];

        if (
            empty($name) ||
            empty($location['city']) ||
            empty($location['address']) ||
            empty($location['zip_code']) ||
            empty($location['country_code']) ||
            empty($location['phone_number'])
        ) {
            throw new BadRequest(['error' => 'Invalid input']);
        }

        $locationId = $this->locationRepo->create($location);
        $facilityId = $this->facilityRepo->create($name, $locationId);

        foreach ($tags as $tagName) {
            if ($tagName === '') continue;
            $tagId = $this->tagRepo->createIfNotExists($tagName);
            $this->tagRepo->addTagToFacility($facilityId, $tagId);
        }

        foreach ($employees as $emp) {
            $emp = array_map([$this, 'sanitizeString'], $emp);
            if ($emp['name'] && $emp['email'] && $emp['phone'] && $emp['position']) {
                $this->employeeRepo->create($facilityId, $emp);
            }
        }

        (new Created(['id' => $facilityId]))->send();
    }

    /**
     * Update a facility, location, tags, and employees.
     */
    public function update($id)
    {
        $data = json_decode(file_get_contents('php://input'), true);

        $name = $this->sanitizeString($data['name'] ?? '');
        $location = array_map([$this, 'sanitizeString'], $data['location'] ?? []);
        $tags = array_map([$this, 'sanitizeString'], isset($data['tags']) && is_array($data['tags']) ? $data['tags'] : []);
        $employees = isset($data['employees']) && is_array($data['employees']) ? $data['employees'] : [];

        // Find existing facility and its location
        $facility = $this->facilityRepo->getById($id);
        if (!$facility) {
            throw new NotFound(['error' => 'Facility not found']);
        }

        $locationId = $this->facilityRepo->getLocationId($id);
        if (!$locationId) {
            throw new NotFound(['error' => 'Location not found']);
        }

        $this->locationRepo->update($locationId, $location);
        $this->facilityRepo->update($id, $name);

        $this->tagRepo->deleteFacilityTags($id);
        foreach ($tags as $tagName) {
            if ($tagName === '') continue;
            $tagId = $this->tagRepo->createIfNotExists($tagName);
            $this->tagRepo->addTagToFacility($id, $tagId);
        }

        $this->employeeRepo->deleteAllByFacility($id);
        foreach ($employees as $emp) {
            $emp = array_map([$this, 'sanitizeString'], $emp);
            if ($emp['name'] && $emp['email'] && $emp['phone'] && $emp['position']) {
                $this->employeeRepo->create($id, $emp);
            }
        }

        (new Ok(['message' => 'Facility updated']))->send();
    }

    /**
     * Delete a facility (CASCADE deletes tags and employees).
     */
    public function delete($id)
    {
        $deleted = $this->facilityRepo->delete($id);
        if (!$deleted) {
            throw new NotFound(['error' => 'Facility not found']);
        }
        (new NoContent())->send();
    }
}
