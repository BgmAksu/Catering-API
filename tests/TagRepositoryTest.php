<?php

namespace tests;

use App\Repositories\FacilityRepository;
use App\Repositories\LocationRepository;
use PDO;
use PDOException;
use PHPUnit\Framework\TestCase;
use App\Repositories\TagRepository;

/**
 * Test scenarios for Tag Repository
 */
class TagRepositoryTest extends TestCase
{
    /**
     * @var PDO
     */
    protected $pdo;
    /**
     * @var TagRepository
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
        $this->repo = new TagRepository($this->pdo);
        $this->locRepo = new LocationRepository($this->pdo);
        $this->facRepo = new FacilityRepository($this->pdo);

        $location = [
            'city' => 'Test Tag City',
            'address' => 'Test Address 123',
            'zip_code' => '98765',
            'country_code' => 'NL',
            'phone_number' => '+31000000000'
        ];
        $locId = $this->locRepo->create($location);
        $this->facilityId = $this->facRepo->create('Test Tag Facility', $locId);
    }

    /**
     * @return void
     */
    public function testCreateTagIfNotExistsSuccessfully()
    {
        // Create a new tag
        $name = 'unitTestTag_' . uniqid();

        // First Creation
        $id1 = $this->repo->createIfNotExists($name);
        $this->assertIsNumeric($id1);

        // Second Creation for checking uniqueness
        $id2 = $this->repo->createIfNotExists($name);
        $this->assertFalse($id2);

        // Find id by name
        $foundId = $this->repo->findIdByName($name);
        $this->assertEquals($id1, $foundId);
    }

    /**
     * @return void
     */
    public function testUpdateTagIfNameUniqueReturnsSuccessfully()
    {
        // Create tag
        $name = 'unitUpdateTag_' . uniqid();
        $newName = $name . '_updated';

        $id = $this->repo->createIfNotExists($name);
        $this->assertIsNumeric($id);

        // Update tag name with new unique one
        $updated = $this->repo->updateIfNameUnique($id, $newName);
        $this->assertGreaterThan(0, $updated);

        // Should be found by new name with same id
        $foundId = $this->repo->findIdByName($newName);
        $this->assertEquals($id, $foundId);
    }

    /**
     * @return void
     */
    public function testUpdateTagNameDuplicateFails()
    {
        // Create tags
        $name1 = 'unitTagDup1_' . uniqid();
        $name2 = 'unitTagDup2_' . uniqid();

        $id1 = $this->repo->createIfNotExists($name1);
        $id2 = $this->repo->createIfNotExists($name2);
        $this->assertIsNumeric($id1);
        $this->assertIsNumeric($id2);

        // Try to update second tag's name to first one's name (should be failed)
        $updated = $this->repo->updateIfNameUnique($id2, $name1);
        $this->assertFalse($updated);

        // Both tags should still be found by their original names and ids
        $this->assertEquals($id1, $this->repo->findIdByName($name1));
        $this->assertEquals($id2, $this->repo->findIdByName($name2));
    }

    /**
     * @return void
     */
    public function testDelete()
    {
        // Create tag
        $name = 'unitDeleteTag_' . uniqid();
        $id = $this->repo->createIfNotExists($name);

        // Delete tag
        $deleted = $this->repo->delete($id);
        $this->assertGreaterThan(0, $deleted);

        // Verify deletion
        $fetched = $this->repo->getById($id);
        $this->assertFalse($fetched);
    }

    /**
     * @return void
     */
    public function testDeleteTagInUseByFacilityThrowsException()
    {
        // Step 1: Create a tag
        $tagName = 'DeleteTestTag_' . uniqid();
        $tagId = $this->repo->create($tagName);

        // Step 2: Create a location and facility to use this tag
        $location = [
            'city' => 'TagTestCity',
            'address' => 'TagTestAddr',
            'zip_code' => '54321',
            'country_code' => 'NL',
            'phone_number' => '+31099999999'
        ];
        $locationId = $this->locRepo->create($location);
        $facilityName = 'Tag Test Facility Delete' . uniqid();
        $facilityId = $this->facRepo->create($facilityName, $locationId);

        // Step 3: Attach the tag to the facility
        $this->repo->addTagToFacility($facilityId, $tagId);

        // Step 4: Try to delete the tag - should return 0
        $deleted = $this->repo->delete($tagId);
        $this->assertEquals(0, $deleted, "Deleting a tag in use should return 0.");
    }


    /**
     * @return void
     */
    public function testAddAndRemoveTagToFacility()
    {
        // Create tag
        $tagName = 'unitFacilityTag_' . uniqid();
        $tagId = $this->repo->createIfNotExists($tagName);

        // Ensure tag is not attached to facility yet
        $hasTag = $this->repo->facilityHasTag($this->facilityId, $tagId);
        $this->assertFalse($hasTag, "Tag should not be attached yet.");

        // Add tag to facility
        $rowsAdded = $this->repo->addTagToFacility($this->facilityId, $tagId);
        $this->assertEquals(1, $rowsAdded, "Tag should be added to facility.");

        // Now facility has that tag
        $hasTag = $this->repo->facilityHasTag($this->facilityId, $tagId);
        $this->assertTrue($hasTag, "Now facility has tag.");

        // Try adding again, should not add duplicate
        $this->expectException(PDOException::class);
        $rowsAddedAgain = $this->repo->addTagToFacility($this->facilityId, $tagId);
        $this->assertEquals(0, $rowsAddedAgain, "No duplicate tag addition to facility.");

        // Remove tag from facility
        $rowsRemoved = $this->repo->removeTagFromFacility($this->facilityId, $tagId);
        $this->assertEquals(1, $rowsRemoved, "Tag should be removed from facility.");

        // Now facility has not that tag
        $hasTag = $this->repo->facilityHasTag($this->facilityId, $tagId);
        $this->assertFalse($hasTag, "Now facility has not that tag.");

        // Remove again, should not affect any row
        $rowsRemovedAgain = $this->repo->removeTagFromFacility($this->facilityId, $tagId);
        $this->assertEquals(0, $rowsRemovedAgain, "Removing nonexistent tag from facility.");
    }

    /**
     * @return void
     */
    public function testGetPaginatedReturnsCorrectTags()
    {
        $baseline = (int)$this->pdo->query("SELECT COALESCE(MAX(id), 0) FROM tags")->fetchColumn();

        // Insert 3 tags to test pagination (names are unique)
        $name1 = 'unitPaginateTag1_' . uniqid();
        $name2 = 'unitPaginateTag2_' . uniqid();
        $name3 = 'unitPaginateTag3_' . uniqid();

        $id1 = (int)$this->repo->createIfNotExists($name1);
        $id2 = (int)$this->repo->createIfNotExists($name2);
        $id3 = (int)$this->repo->createIfNotExists($name3);

        // Test: Get paginated tags (limit 2)
        [$rows, $nextCursor] = $this->repo->getPaginated(2, $baseline+1);

        $this->assertIsArray($rows);
        $this->assertGreaterThanOrEqual(2, count($rows));

        // Check test tags are included in results
        $ids = array_map('intval', array_column($rows, 'id'));
        $this->assertContains($id1, $ids);
        $this->assertContains($id2, $ids);
        $this->assertSame($id3, (int)$nextCursor);
    }
}