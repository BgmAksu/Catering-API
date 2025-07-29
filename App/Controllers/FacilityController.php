<?php

namespace App\Controllers;

use App\Helper\Sanitizer;
use App\Helper\Validator;
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
            'name' => isset($_GET['name']) ? Sanitizer::string($_GET['name']) : null,
            'tag' => isset($_GET['tag']) ? Sanitizer::string($_GET['tag']) : null,
            'city' => isset($_GET['city']) ? Sanitizer::string($_GET['city']) : null,
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

        $name = Sanitizer::string($data['name'] ?? '');
        $location = Sanitizer::sanitizeAll($data['location'] ?? []);
        $tags = Sanitizer::sanitizeAll($data['tags'] ?? []);
        $employees = isset($data['employees']) && is_array($data['employees']) ? Sanitizer::sanitizeAll($data['employees']) : [];

        if (
            !Validator::notEmpty($name) ||
            !Validator::notEmpty($location['city'] ?? '') ||
            !Validator::notEmpty($location['address'] ?? '') ||
            !Validator::zipCode($location['zip_code'] ?? '') ||
            !Validator::countryCode($location['country_code'] ?? '') ||
            !Validator::phone($location['phone_number'] ?? '')
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
            if (
                Validator::notEmpty($emp['name'] ?? '') &&
                Validator::email($emp['email'] ?? '') &&
                Validator::phone($emp['phone'] ?? '') &&
                Validator::notEmpty($emp['position'] ?? '')
            ) {
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

        $name = Sanitizer::string($data['name'] ?? '');
        $location = Sanitizer::sanitizeAll($data['location'] ?? []);
        $tags = Sanitizer::sanitizeAll($data['tags'] ?? []);
        $employees = isset($data['employees']) && is_array($data['employees']) ? Sanitizer::sanitizeAll($data['employees']) : [];

        if (
            !Validator::notEmpty($name) ||
            !Validator::notEmpty($location['city'] ?? '') ||
            !Validator::notEmpty($location['address'] ?? '') ||
            !Validator::zipCode($location['zip_code'] ?? '') ||
            !Validator::countryCode($location['country_code'] ?? '') ||
            !Validator::phone($location['phone_number'] ?? '')
        ) {
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

        $this->locationRepo->update($locationId, $location);
        $this->facilityRepo->update($id, $name);

        // These parts (tag and employee) can be changed
        $this->tagRepo->deleteFacilityTags($id);
        foreach ($tags as $tagName) {
            if ($tagName === '') continue;
            $tagId = $this->tagRepo->createIfNotExists($tagName);
            $this->tagRepo->addTagToFacility($id, $tagId);
        }

        $this->employeeRepo->deleteAllByFacility($id);
        foreach ($employees as $emp) {
            if (
                Validator::notEmpty($emp['name'] ?? '') &&
                Validator::email($emp['email'] ?? '') &&
                Validator::phone($emp['phone'] ?? '') &&
                Validator::notEmpty($emp['position'] ?? '')
            ) {
                $this->employeeRepo->create($id, $emp);
            }
        }

        (new Ok(['message' => 'Facility updated']))->send();
    }

    /**
     * Delete a facility.
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
