<?php

namespace tests;

use App\Repositories\FacilityRepository;
use PDO;
use PHPUnit\Framework\TestCase;
use App\Repositories\LocationRepository;

/**
 * Test scenarios for Location Repository
 */
class LocationRepositoryTest extends TestCase
{
    /**
     * @var PDO
     */
    protected $pdo;
    /**
     * @var LocationRepository
     */
    protected $repo;
    /**
     * @var FacilityRepository
     */
    protected $facRepo;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->pdo = new PDO('mysql:host=testdb;dbname=testdb;port=3306', 'testuser', 'testpass');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->repo = new LocationRepository($this->pdo);
        $this->facRepo = new FacilityRepository($this->pdo);

    }

    /**
     * @return void
     */
    public function testCreateAndGetById()
    {
        // Create location
        $location = [
            'city' => 'Test City',
            'address' => 'Test Address 123',
            'zip_code' => '98765',
            'country_code' => 'NL',
            'phone_number' => '+31000000000'
        ];
        $id = $this->repo->create($location);
        $this->assertIsNumeric($id);

        // Get location by id
        $fetched = $this->repo->getById($id);
        $this->assertIsArray($fetched);
        $this->assertEquals('Test City', $fetched['city']);
        $this->assertEquals('Test Address 123', $fetched['address']);
    }

    /**
     * @return void
     */
    public function testUpdate()
    {
        // Insert location
        $location = [
            'city' => 'ToBeUpdated',
            'address' => 'Address X',
            'zip_code' => '00000',
            'country_code' => 'NL',
            'phone_number' => '+31123456789'
        ];
        $id = $this->repo->create($location);

        // Update location data
        $location['city'] = 'Updated City';
        $location['address'] = 'Updated Address';
        $affected = $this->repo->update($id, $location);
        $this->assertGreaterThanOrEqual(0, $affected);

        // Check updated values
        $fetched = $this->repo->getById($id);
        $this->assertEquals('Updated City', $fetched['city']);
        $this->assertEquals('Updated Address', $fetched['address']);
    }

    /**
     * @return void
     */
    public function testDelete()
    {
        // Insert location
        $location = [
            'city' => 'ToBeDeleted',
            'address' => 'Del Address',
            'zip_code' => '22222',
            'country_code' => 'NL',
            'phone_number' => '+31022222222'
        ];
        $id = $this->repo->create($location);

        // Delete location
        $deleted = $this->repo->delete($id);
        $this->assertGreaterThan(0, $deleted);

        // Verify deletion
        $fetched = $this->repo->getById($id);
        $this->assertFalse($fetched);
    }

    /**
     * @return void
     */
    public function testDeleteLocationInUseByFacilityThrowsException()
    {
        // Step 1: Create a location
        $location = [
            'city' => 'DeleteTestCity',
            'address' => 'DeleteTestAddr',
            'zip_code' => '12345',
            'country_code' => 'NL',
            'phone_number' => '+31011111111'
        ];
        $locationId = $this->repo->create($location);

        // Step 2: Create a facility using this location
        $facilityName = 'Delete Test Facility Location' . uniqid();
        $facilityId = $this->facRepo->create($facilityName, $locationId);

        // Step 3: Try to delete the location - should return 0
        $deleted = $this->repo->delete($locationId);
        $this->assertEquals(0, $deleted, "Deleting a location in use should return 0.");
    }

    /**
     * @return void
     */
    public function testGetPaginated()
    {
        // Insert multiple locations for pagination
        $location1 = [
            'city' => 'Paginate1',
            'address' => 'Addr1',
            'zip_code' => '11111',
            'country_code' => 'NL',
            'phone_number' => '+31011111111'
        ];
        $location2 = [
            'city' => 'Paginate2',
            'address' => 'Addr2',
            'zip_code' => '22222',
            'country_code' => 'NL',
            'phone_number' => '+31022222222'
        ];
        $id1 = $this->repo->create($location1);
        $id2 = $this->repo->create($location2);

        // Get paginated locations with cursor
        $list = $this->repo->getPaginated(2, 0);
        $this->assertIsArray($list);
        $this->assertNotEmpty($list);
    }
}