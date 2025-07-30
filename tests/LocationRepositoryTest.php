<?php

namespace tests;

use PDO;
use PHPUnit\Framework\TestCase;
use App\Repositories\LocationRepository;

class LocationRepositoryTest extends TestCase
{
    protected $pdo;
    protected $repo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('mysql:host=localhost;dbname=testdb', 'testuser', 'testpass');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->repo = new LocationRepository($this->pdo);
    }

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