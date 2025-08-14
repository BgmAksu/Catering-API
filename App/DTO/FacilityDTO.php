<?php

namespace App\DTO;

use App\Enums\ValidationError;
use App\Helper\Sanitizer;
use App\Helper\Validator;

/**
 * Data Transfer Object for Facility (supports create and partial update)
 */
class FacilityDTO
{
    /** @var string */
    public string $name = '';

    /** @var array{city?:string,address?:string,zip_code?:string,country_code?:string,phone_number?:string} */
    public array $location = [];

    /** @var string[] */
    public array $tags = [];

    /**
     * Whether this DTO is used for update (partial)
     * @var bool
     */
    private bool $isUpdate = false;

    /**
     * Tracks which fields were provided in input
     * @var array
     */
    private array $provided = [
        'name' => false,
        'location' => [
            '_provided'     => false,
            'city'          => false,
            'address'       => false,
            'zip_code'      => false,
            'country_code'  => false,
            'phone_number'  => false,
        ],
        'tags' => false,
    ];

    /**
     * Set true if "tags" is provided but not an array
     * @var bool
     */
    private bool $invalidTagsType = false;

    /**
     * @param array $data Raw request body
     * @param bool  $isUpdate If true, only provided fields will be validated
     */
    public function __construct(array $data, bool $isUpdate = false)
    {
        $this->isUpdate = $isUpdate;

        // Provided flags
        $this->provided['name'] = array_key_exists('name', $data);
        $this->provided['tags'] = array_key_exists('tags', $data);

        // Name
        $this->name = Sanitizer::string($data['name'] ?? '');

        // Location
        $locRaw = (isset($data['location']) && is_array($data['location'])) ? $data['location'] : null;
        $this->provided['location']['_provided'] = $locRaw !== null;
        if ($locRaw !== null) {
            foreach (['city','address','zip_code','country_code','phone_number'] as $k) {
                $this->provided['location'][$k] = array_key_exists($k, $locRaw);
            }
            $this->location = $locRaw; // sanitized field-by-field in validation
        }

        // Tags
        if ($this->provided['tags']) {
            if (!is_array($data['tags'])) {
                $this->invalidTagsType = true;
                $this->tags = [];
            } else {
                $clean = [];
                foreach ($data['tags'] as $t) {
                    $s = Sanitizer::string($t);
                    if ($s !== '') {
                        $clean[] = $s;
                    }
                }
                $this->tags = $clean; // empty array means "clear all" if you choose full-sync policy
            }
        }
    }

    /**
     * True if this DTO is in update mode (partial)
     * @return bool
     */
    public function isUpdate(): bool
    {
        return $this->isUpdate;
    }

    /**
     * True when update payload contains no updatable fields at all
     * @return bool
     */
    public function isEmptyPayload(): bool
    {
        $locProvided = $this->provided['location']['_provided'] &&
            (
                $this->provided['location']['city'] ||
                $this->provided['location']['address'] ||
                $this->provided['location']['zip_code'] ||
                $this->provided['location']['country_code'] ||
                $this->provided['location']['phone_number']
            );

        return !$this->provided['name']
            && !$this->provided['tags']
            && !$locProvided;
    }

    /**
     * Valid when there are no field errors
     * @return bool
     */
    public function isValid(): bool
    {
        return empty($this->errors());
    }

    /**
     * Field-level validation errors
     * @return array<string,string>
     */
    public function errors(): array
    {
        $errors = [];

        // Helper getters with sanitize
        $city   = isset($this->location['city']) ? Sanitizer::string($this->location['city']) : null;
        $addr   = isset($this->location['address']) ? Sanitizer::string($this->location['address']) : null;
        $zip    = isset($this->location['zip_code']) ? Sanitizer::string($this->location['zip_code']) : null;
        $cc     = isset($this->location['country_code']) ? Sanitizer::string($this->location['country_code']) : null;
        $phone  = isset($this->location['phone_number']) ? Sanitizer::string($this->location['phone_number']) : null;

        if ($this->isUpdate) {
            // Block completely empty update payload
            if ($this->isEmptyPayload()) {
                $errors['payload'] = ValidationError::AT_LEAST_ONE_FIELD_REQUIRED->value;
                return $errors;
            }

            if ($this->provided['name'] && !Validator::notEmpty($this->name)) {
                $errors['name'] = ValidationError::CANNOT_BE_EMPTY->value;
            }

            if ($this->provided['location']['_provided']) {
                if ($this->provided['location']['city'] && !Validator::notEmpty((string)$city)) {
                    $errors['location.city'] = ValidationError::CANNOT_BE_EMPTY->value;
                }
                if ($this->provided['location']['address'] && !Validator::notEmpty((string)$addr)) {
                    $errors['location.address'] = ValidationError::CANNOT_BE_EMPTY->value;
                }
                if ($this->provided['location']['zip_code'] && !Validator::zipCode((string)$zip)) {
                    $errors['location.zip_code'] = ValidationError::INVALID_ZIP_CODE->value;
                }
                if ($this->provided['location']['country_code'] && !Validator::countryCode((string)$cc)) {
                    $errors['location.country_code'] = ValidationError::INVALID_COUNTRY_CODE->value;
                }
                if ($this->provided['location']['phone_number'] && !Validator::phone((string)$phone)) {
                    $errors['location.phone_number'] = ValidationError::INVALID_PHONE->value;
                }
            }

            if ($this->provided['tags'] && $this->invalidTagsType) {
                $errors['tags'] = ValidationError::MUST_BE_ARRAY_OF_STRINGS->value;
            }
        } else {
            // CREATE: all required fields must be present & valid
            if (!Validator::notEmpty($this->name)) {
                $errors['name'] = ValidationError::REQUIRED->value;
            }
            if (!Validator::notEmpty((string)$city)) {
                $errors['location.city'] = ValidationError::CANNOT_BE_EMPTY->value;
            }
            if (!Validator::notEmpty((string)$addr)) {
                $errors['location.address'] = ValidationError::REQUIRED->value;
            }
            if (!Validator::zipCode((string)$zip)) {
                $errors['location.zip_code'] = ValidationError::INVALID_ZIP_CODE->value;
            }
            if (!Validator::countryCode((string)$cc)) {
                $errors['location.country_code'] = ValidationError::INVALID_COUNTRY_CODE->value;
            }
            if (!Validator::phone((string)$phone)) {
                $errors['location.phone_number'] = ValidationError::INVALID_PHONE->value;
            }
        }

        return $errors;
    }

    /**
     * Helper to check if a field (or subfield) was sent in the payload
     * @param string $field
     * @param string|null $sub
     * @return bool
     */
    public function provided(string $field, ?string $sub = null): bool
    {
        if ($sub === null) {
            return (bool)($this->provided[$field] ?? false);
        }
        return (bool)($this->provided[$field][$sub] ?? false);
    }
}
