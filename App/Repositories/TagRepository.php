<?php

namespace App\Repositories;

class TagRepository
{
    protected $pdo;
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function createIfNotExists($tagName)
    {
        $stmt = $this->pdo->prepare("SELECT id FROM tags WHERE name = ?");
        $stmt->execute([$tagName]);
        $tagId = $stmt->fetchColumn();
        if (!$tagId) {
            $stmt = $this->pdo->prepare("INSERT INTO tags (name) VALUES (?)");
            $stmt->execute([$tagName]);
            $tagId = $this->pdo->lastInsertId();
        }
        return $tagId;
    }

    public function deleteFacilityTags($facilityId)
    {
        $stmt = $this->pdo->prepare("DELETE FROM facility_tags WHERE facility_id=?");
        $stmt->execute([$facilityId]);
    }

    public function addTagToFacility($facilityId, $tagId)
    {
        $stmt = $this->pdo->prepare("INSERT INTO facility_tags (facility_id, tag_id) VALUES (?, ?)");
        $stmt->execute([$facilityId, $tagId]);
    }
}