<?php

namespace tests;

use PDO;
use PHPUnit\Framework\TestCase;
use App\Repositories\FacilityRepository;
use App\Repositories\LocationRepository;

class FacilityRepositoryTest extends TestCase
{
    protected $pdo;
    protected $repo;
    protected $locRepo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('mysql:host=localhost;dbname=testdb', 'testuser', 'testpass');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->repo = new FacilityRepository($this->pdo);
        $this->locRepo = new LocationRepository($this->pdo);
    }

    public function testCreateAndGetById()
    {
        // Create a new location for facility
        $location = [
            'city' => 'UnitTestCity',
            'address' => 'UnitTestAddress',
            'zip_code' => '99999',
            'country_code' => 'NL',
            'phone_number' => '+31098765432'
        ];
        $locationId = $this->locRepo->create($location);

        // Create facility
        $name = 'UnitTest Facility ' . uniqid();
        $id = $this->repo->create($name, $locationId);
        $this->assertIsNumeric($id);

        // Fetch by id
        $facility = $this->repo->getById($id);
        $this->assertIsArray($facility);
        $this->assertEquals($name, $facility['name']);
        $this->assertEquals($location['city'], $facility['location']['city']);
    }

    public function testUpdate()
    {
        // Create location and facility
        $location = [
            'city' => 'ToUpdateCity',
            'address' => 'ToUpdateAddress',
            'zip_code' => '88888',
            'country_code' => 'NL',
            'phone_number' => '+31088888888'
        ];
        $locationId = $this->locRepo->create($location);

        $name = 'UpdateMe Facility ' . uniqid();
        $id = $this->repo->create($name, $locationId);

        // Update facility name
        $newName = $name . ' Updated';
        $affected = $this->repo->update($id, $newName);
        $this->assertGreaterThanOrEqual(0, $affected);

        // Verify update
        $facility = $this->repo->getById($id);
        $this->assertEquals($newName, $facility['name']);
    }

    public function testDelete()
    {
        // Create location and facility
        $location = [
            'city' => 'DeleteCity',
            'address' => 'DeleteAddress',
            'zip_code' => '77777',
            'country_code' => 'NL',
            'phone_number' => '+31077777777'
        ];
        $locationId = $this->locRepo->create($location);

        $name = 'DeleteMe Facility ' . uniqid();
        $id = $this->repo->create($name, $locationId);

        // Delete facility
        $deleted = $this->repo->delete($id);
        $this->assertGreaterThan(0, $deleted);

        // Facility should not exist anymore
        $facility = $this->repo->getById($id);
        $this->assertIsArray($facility);

        // Expect not found:
        $this->assertEmpty($facility);
    }

    public function testGetPaginated()
    {
        // Insert two locations for new facilities
        $location1 = [
            'city' => 'PaginateCity1',
            'address' => 'PaginateAddr1',
            'zip_code' => '10101',
            'country_code' => 'NL',
            'phone_number' => '+31011111111'
        ];
        $location2 = [
            'city' => 'PaginateCity2',
            'address' => 'PaginateAddr2',
            'zip_code' => '20202',
            'country_code' => 'NL',
            'phone_number' => '+31022222222'
        ];
        $locId1 = $this->locRepo->create($location1);
        $locId2 = $this->locRepo->create($location2);

        // Insert two facilities
        $name1 = 'PagFacility1_' . uniqid();
        $name2 = 'PagFacility2_' . uniqid();
        $id1 = $this->repo->create($name1, $locId1);
        $id2 = $this->repo->create($name2, $locId2);

        // Test: Get paginated facilities (limit 2)
        list($facilities, $maxId) = $this->repo->getPaginated(2, $id1 - 1);

        $this->assertIsArray($facilities);
        $facilityIds = array_map('intval', array_column($facilities, 'id'));

        // Assert both test facilities are in the result
        $this->assertContains((int)$id1, $facilityIds, "Facility id1 ($id1) should be in paginated results.");
        $this->assertContains((int)$id2, $facilityIds, "Facility id2 ($id2) should be in paginated results.");

        // Check returned maxId is as expected
        $this->assertEquals(max($id1, $id2), $maxId);
    }

    public function testGetLocationId()
    {
        // Create location and facility
        $location = [
            'city' => 'FindLocCity',
            'address' => 'FindLocAddr',
            'zip_code' => '33333',
            'country_code' => 'NL',
            'phone_number' => '+31033333333'
        ];
        $locationId = $this->locRepo->create($location);

        $name = 'FindLoc Facility ' . uniqid();
        $id = $this->repo->create($name, $locationId);

        // Test getLocationId
        $foundLocationId = $this->repo->getLocationId($id);
        $this->assertEquals($locationId, $foundLocationId);
    }
}