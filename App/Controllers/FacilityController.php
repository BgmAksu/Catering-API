<?php

namespace App\Controllers;

use App\DTO\FacilityDTO;
use App\DTO\TagDTO;
use App\Helper\Request;
use App\Helper\Sanitizer;
use App\Middleware\Authenticate;
use App\Plugins\Di\Injectable;
use App\Plugins\Http\Exceptions\UnprocessableEntity;
use App\Plugins\Http\Response\Ok;
use App\Plugins\Http\Response\Created;
use App\Plugins\Http\Response\NoContent;
use App\Plugins\Http\Exceptions\BadRequest;
use App\Plugins\Http\Exceptions\NotFound;
use App\Repositories\FacilityRepository;
use App\Repositories\EmployeeRepository;
use App\Repositories\TagRepository;
use App\Repositories\LocationRepository;
use Throwable;

class FacilityController extends Injectable
{
    /**
     * @var
     */
    protected $pdo;
    /**
     * @var FacilityRepository
     */
    protected FacilityRepository $facilityRepo;
    /**
     * @var EmployeeRepository
     */
    protected EmployeeRepository $employeeRepo;
    /**
     * @var TagRepository
     */
    protected TagRepository $tagRepo;
    /**
     * @var LocationRepository
     */
    protected LocationRepository $locationRepo;


    public function __construct()
    {
        Authenticate::check();
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

        list($facilities, $nextCursor) = $this->facilityRepo->getPaginated($limit, $cursor);
        foreach ($facilities as $fid => &$fac) {
            $fac['employees'] = $this->employeeRepo->getByFacility($fid);
        }

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
            'tag'  => isset($_GET['tag']) ? Sanitizer::string($_GET['tag'])   : null,
            'city' => isset($_GET['city']) ? Sanitizer::string($_GET['city']) : null,
        ];

        $limit = Request::limitDecider();
        $cursor = Request::cursorDecider();

        list($facilities, $nextCursor) = $this->facilityRepo->getPaginated($limit, $cursor, $filters);
        foreach ($facilities as $fid => &$fac) {
            $fac['employees'] = $this->employeeRepo->getByFacility($fid);
        }

        (new Ok([
            'limit' => $limit,
            'cursor' => $cursor,
            'next_cursor' => $nextCursor,
            'facilities' => array_values($facilities)
        ]))->send();
    }

    /**
     * Get detail of a facility with location, tags, and employees.
     * GET /api/facilities/{facility_id}
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
     * POST /api/facilities
     * Body: { "name": "...", "location": {...}, "tags": [...] }
     * @return void
     * @throws UnprocessableEntity|Throwable
     */
    public function create(): void
    {
        $data = Request::getJsonData();
        $dto = new FacilityDTO($data);

        if (!$dto->isValid()) {
            throw new UnprocessableEntity([
                'message' => 'Validation failed',
                'errors'  => $dto->errors(),
            ]);
        }

        // Use a single transaction for location + facility + tags
        $this->pdo->beginTransaction();
        try {
            $locationId = $this->locationRepo->create($dto->location);
            $facilityId = $this->facilityRepo->create($dto->name, $locationId);

            // Tags: ensure we always resolve a valid tag id before attaching
            foreach ($dto->tags as $rawName) {
                // normalize and skip empty values
                $tagName = trim((string)$rawName);
                if ($tagName === '') {
                    continue;
                }

                // Try to create; when it already exists this may return false
                $tagId = $this->tagRepo->createIfNotExists($tagName);

                // Fallback: if creation returned false (already exists), look up the id
                if (!$tagId) {
                    $tagId = $this->tagRepo->findIdByName($tagName);
                }

                // If still not found, skip safely
                if (!$tagId) {
                    continue;
                }

                // Avoid duplicates on the pivot table
                if (!$this->tagRepo->facilityHasTag($facilityId, (int)$tagId)) {
                    $this->tagRepo->addTagToFacility($facilityId, (int)$tagId);
                }
            }

            $this->pdo->commit();
            (new Created(['id' => $facilityId]))->send();
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Update a facility by ID.
     * PUT /api/facilities/{facility_id}
     * Body: { "name": "...", "location": {...} }
     * Only provided fields are validated and updated.
     * @param $id
     * @return void
     * @throws BadRequest
     * @throws NotFound
     */
    public function update($id): void
    {
        $data = Request::getJsonData();
        $dto = new FacilityDTO(is_array($data) ? $data : [], true); // update mode

        if (!$dto->isValid()) {
            // Return field-level 422 instead of a generic 400
            throw new UnprocessableEntity([
                'message' => 'Validation failed',
                'errors'  => $dto->errors(),
            ]);
        }

        // Ensure facility exists (and get current location id)
        $facility = $this->facilityRepo->getById($id);
        if (!$facility) {
            throw new NotFound(['error' => 'Facility not found']);
        }
        $locationId = $this->facilityRepo->getLocationId($id);
        if (!$locationId) {
            throw new NotFound(['error' => 'Location not found']);
        }

        $this->pdo->beginTransaction();
        try {
            // Patch "name" if provided
            if ($dto->provided('name')) {
                $this->facilityRepo->update($id, $dto->name);
            }

            // Patch "location" fields if provided
            if ($dto->provided('location', '_provided')) {
                $locPatch = [];
                foreach (['city','address','zip_code','country_code','phone_number'] as $k) {
                    if ($dto->provided('location', $k)) {
                        $locPatch[$k] = $dto->location[$k] ?? null;
                    }
                }
                if (!empty($locPatch)) {
                    $this->locationRepo->updatePartial((int)$locationId, $locPatch);
                }
            }

            // Tags (PATCH-like): only ADD missing tags; do not remove existing ones
            if (isset($data['tags']) && is_array($data['tags'])) {
                foreach ($data['tags'] as $rawName) {
                    $tagName = trim((string)$rawName);
                    if ($tagName === '') {
                        continue;
                    }

                    // Create or resolve existing id
                    $tagId = $this->tagRepo->createIfNotExists($tagName);
                    if (!$tagId) {
                        $tagId = $this->tagRepo->findIdByName($tagName);
                    }
                    if (!$tagId) {
                        continue;
                    }

                    // Attach only if missing
                    if (!$this->tagRepo->facilityHasTag((int)$id, (int)$tagId)) {
                        $this->tagRepo->addTagToFacility((int)$id, (int)$tagId);
                    }
                }
            }

            $this->pdo->commit();
            (new Ok(['message' => 'Facility updated']))->send();
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Delete a facility by ID.
     * DELETE /api/facilities/{facility_id}
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
     * POST /api/facilities/{facility_id}/tags
     * Body: [ "vegan", "halal" ]
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
     * Remove tag(s) from facility.
     * DELETE /api/facilities/{facility_id}/tags
     * Body: [ "meat" ]
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
                    $this->tagRepo->removeTagFromFacility($facilityId, $tagId);
                    $removed[] = $tagDTO->name;
                }
            }
        }

        (new Ok(['removed' => $removed]))->send();
    }
}
