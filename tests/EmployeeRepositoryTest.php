<?php

namespace tests;

use App\Repositories\FacilityRepository;
use App\Repositories\LocationRepository;
use PDO;
use PHPUnit\Framework\TestCase;
use App\Repositories\EmployeeRepository;

/**
 * Test scenarios for Employee Repository
 */
class EmployeeRepositoryTest extends TestCase
{
    /**
     * @var PDO
     */
    protected $pdo;
    /**
     * @var EmployeeRepository
     */
    protected $repo;
    /**
     * @var LocationRepository
     */
    protected $locRepo;
    /**
     * @var FacilityRepository
     */
    protected $facRepo;
    /**
     * @var mixed
     */
    protected $facilityId; // Make sure this facility exists in test DB

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->pdo = new PDO('mysql:host=testdb;dbname=testdb;port=3306', 'testuser', 'testpass');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->repo = new EmployeeRepository($this->pdo);
        $this->locRepo = new LocationRepository($this->pdo);
        $this->facRepo = new FacilityRepository($this->pdo);

        $location = [
            'city' => 'Test Employee City',
            'address' => 'Test Address 123',
            'zip_code' => '98765',
            'country_code' => 'NL',
            'phone_number' => '+31000000000'
        ];
        $locId = $this->locRepo->create($location);
        $this->facilityId = $this->facRepo->create('Test Employee Facility', $locId);
    }

    /**
     * @return void
     */
    public function testGetPaginatedByFacility()
    {
        // Create multiple employees for pagination
        $emp1 = [
            'name' => 'PaginateEmp1',
            'email' => 'pag1_' . uniqid() . '@test.com',
            'phone' => '+31050000001',
            'position' => 'Pag1'
        ];
        $emp2 = [
            'name' => 'PaginateEmp2',
            'email' => 'pag2_' . uniqid() . '@test.com',
            'phone' => '+31050000002',
            'position' => 'Pag2'
        ];
        $emp3 = [
            'name' => 'PaginateEmp3',
            'email' => 'pag3_' . uniqid() . '@test.com',
            'phone' => '+31050000003',
            'position' => 'Pag3'
        ];

        $id1 = $this->repo->create($this->facilityId, $emp1);
        $id2 = $this->repo->create($this->facilityId, $emp2);
        $id3 = $this->repo->create($this->facilityId, $emp3);

        // Page 1
        [$rows1, $next1] = $this->repo->getPaginatedByFacility($this->facilityId, 1, 0);
        $this->assertCount(1, $rows1);
        $this->assertSame((int)$id1, (int)$rows1[0]['id']);
        $this->assertSame((int)$id2, (int)$next1);

        // Page 2
        [$rows2, $next2] = $this->repo->getPaginatedByFacility($this->facilityId, 1, $next1);
        $this->assertCount(1, $rows2);
        $this->assertSame((int)$id2, (int)$rows2[0]['id']);
        $this->assertSame((int)$id3, (int)$next2);

        // Page 3
        [$rows3, $next3] = $this->repo->getPaginatedByFacility($this->facilityId, 1, $next2);
        $this->assertCount(1, $rows3);
        $this->assertSame((int)$id3, (int)$rows3[0]['id']);
        $this->assertNull($next3);
    }

    /**
     * @return void
     */
    public function testCreateAndGetById()
    {
        // Create a new employee
        $emp = [
            'name' => 'UnitTest Emp',
            'email' => 'unit' . uniqid() . '@test.com',
            'phone' => '+31010000001',
            'position' => 'Tester'
        ];
        $id = $this->repo->create($this->facilityId, $emp);
        $this->assertIsNumeric($id);

        // Fetch employee by id
        $fetched = $this->repo->getById($id);
        $this->assertIsArray($fetched);
        $this->assertEquals($emp['name'], $fetched['name']);
        $this->assertEquals($emp['email'], $fetched['email']);
        $this->assertEquals($emp['phone'], $fetched['phone']);
        $this->assertEquals($emp['position'], $fetched['position']);
        $this->assertEquals($this->facilityId, $fetched['facility_id']);
    }

    /**
     * @return void
     */
    public function testGetByFacility()
    {
        // Add two employees to facility
        $emp1 = [
            'name' => 'Emp1',
            'email' => 'emp1_' . uniqid() . '@test.com',
            'phone' => '+31020000001',
            'position' => 'Position1'
        ];
        $emp2 = [
            'name' => 'Emp2',
            'email' => 'emp2_' . uniqid() . '@test.com',
            'phone' => '+31020000002',
            'position' => 'Position2'
        ];
        $id1 = $this->repo->create($this->facilityId, $emp1);
        $id2 = $this->repo->create($this->facilityId, $emp2);

        // Fetch all employees for facility
        $list = $this->repo->getByFacility($this->facilityId);
        $this->assertIsArray($list);
        $ids = array_map('intval', array_column($list, 'id'));
        $this->assertContains((int)$id1, $ids);
        $this->assertContains((int)$id2, $ids);
    }

    /**
     * @return void
     */
    public function testUpdate()
    {
        // Create employee
        $emp = [
            'name' => 'UpdateMe',
            'email' => 'update_' . uniqid() . '@test.com',
            'phone' => '+31030000001',
            'position' => 'Original'
        ];
        $id = $this->repo->create($this->facilityId, $emp);

        // Update employee
        $partialUpdate['name'] = 'UpdatedName';
        $partialUpdate['position'] = 'UpdatedPosition';
        $affected = $this->repo->updatePartial($id, $partialUpdate);
        $this->assertGreaterThanOrEqual(0, $affected);

        // Verify update
        $fetched = $this->repo->getById((int)$id);
        $this->assertEquals('UpdatedName', $fetched['name']);
        $this->assertEquals('UpdatedPosition', $fetched['position']);
    }


    /**
     * @return void
     */
    public function testDelete()
    {
        // Create employee
        $emp = [
            'name' => 'ToBeDeleted',
            'email' => 'delete_' . uniqid() . '@test.com',
            'phone' => '+31040000001',
            'position' => 'ToDelete'
        ];
        $id = $this->repo->create($this->facilityId, $emp);

        // Delete employee
        $deleted = $this->repo->delete($id);
        $this->assertGreaterThan(0, $deleted);

        // Verify deletion
        $fetched = $this->repo->getById($id);
        $this->assertFalse($fetched);
    }
}
