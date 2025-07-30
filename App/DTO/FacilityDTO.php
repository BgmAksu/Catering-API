<?php

namespace App\DTO;

use App\Helper\Sanitizer;
use App\Helper\Validator;
use App\DTO\EmployeeDTO;

class FacilityDTO
{
    public $name;
    public $location = [];
    public $tags = [];

    public function __construct(array $data)
    {
        $this->name = Sanitizer::string($data['name'] ?? '');
        $this->location = Sanitizer::sanitizeAll($data['location'] ?? []);
        $this->tags = Sanitizer::sanitizeAll($data['tags'] ?? []);
    }

    public function isValid()
    {
        return
            Validator::notEmpty($this->name) &&
            Validator::notEmpty($this->location['city'] ?? '') &&
            Validator::notEmpty($this->location['address'] ?? '') &&
            Validator::zipCode($this->location['zip_code'] ?? '') &&
            Validator::countryCode($this->location['country_code'] ?? '') &&
            Validator::phone($this->location['phone_number'] ?? '');
    }
}