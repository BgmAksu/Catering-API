<?php

namespace App\DTO;

use App\Helper\Sanitizer;
use App\Helper\Validator;
use App\DTO\EmployeeDTO;

/**
 * Data Transfer Object for Facility
 */
class FacilityDTO
{
    /** @var string */
    public string $name;

    /** @var array{city?:string,address?:string,zip_code?:string,country_code?:string,phone_number?:string} */
    public array $location = [];

    /** @var string[] */
    public array $tags = [];

    /**
     * @param array $data raw request body
     */
    public function __construct(array $data)
    {
        $this->name = Sanitizer::string($data['name'] ?? '');
        $this->location = Sanitizer::sanitizeAll($data['location'] ?? []);

        // controls for tags
        $rawTags = is_array($data['tags'] ?? null) ? $data['tags'] : [];
        $clean = [];
        foreach ($rawTags as $t) {
            $s = Sanitizer::string($t);
            if ($s !== '') {
                $clean[] = $s;
            }
        }
        $this->tags = $clean;
    }

    /**
     * Simple boolean validation
     * @return bool
     */
    public function isValid(): bool
    {
        return empty($this->errors());
    }

    /**
     * Field-level validation errors to enable 422 responses
     * @return array<string,string>
     */
    public function errors(): array
    {
        $errors = [];

        if (!Validator::notEmpty($this->name)) {
            $errors['name'] = 'required';
        }

        $city = $this->location['city'] ?? '';
        $address = $this->location['address'] ?? '';
        $zip = $this->location['zip_code'] ?? '';
        $cc = $this->location['country_code'] ?? '';
        $phone = $this->location['phone_number'] ?? '';

        if (!Validator::notEmpty($city)) {
            $errors['location.city'] = 'required';
        }
        if (!Validator::notEmpty($address)) {
            $errors['location.address'] = 'required';
        }
        if (!Validator::zipCode($zip)) {
            $errors['location.zip_code'] = 'invalid';
        }
        if (!Validator::countryCode($cc)) {
            $errors['location.country_code'] = 'invalid';
        }
        if (!Validator::phone($phone)) {
            $errors['location.phone_number'] = 'invalid';
        }

        return $errors;
    }
}