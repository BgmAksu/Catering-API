<?php
namespace App\DTO;

use App\Enums\ValidationError;
use App\Helper\Sanitizer;
use App\Helper\Validator;

/**
 * Data Transfer Object for Location
 */
class LocationDTO
{
    /**
     * @var string
     */
    public string $city;

    /**
     * @var string
     */
    public string $address;

    /**
     * @var string
     */
    public string $zip_code;

    /**
     * @var string
     */
    public string $country_code;

    /**
     * @var string|array|null
     */
    public string|array|null $phone_number;

    /** Whether this DTO is used for update (partial) */
    private bool $isUpdate = false;

    /** Tracks which fields were provided */
    private array $provided = [
        'city'         => false,
        'address'      => false,
        'zip_code'     => false,
        'country_code' => false,
        'phone_number' => false,
    ];

    public function __construct(array $data, bool $isUpdate = false)
    {
        $this->isUpdate = $isUpdate;

        foreach (array_keys($this->provided) as $k) {
            $this->provided[$k] = array_key_exists($k, $data);
        }

        $this->city          = Sanitizer::string($data['city'] ?? '');
        $this->address       = Sanitizer::string($data['address'] ?? '');
        $this->zip_code      = Sanitizer::string($data['zip_code'] ?? '');
        $this->country_code  = Sanitizer::string($data['country_code'] ?? '');
        $this->phone_number  = Sanitizer::string($data['phone_number'] ?? '');
    }

    /** True when update payload contains no updatable fields */
    public function isEmptyPayload(): bool
    {
        foreach ($this->provided as $v) {
            if ($v) return false;
        }
        return true;
    }

    /** Field-level validation errors */
    public function errors(): array
    {
        $errors = [];

        if ($this->isUpdate) {
            if ($this->isEmptyPayload()) {
                $errors['payload'] = ValidationError::AT_LEAST_ONE_FIELD_REQUIRED->value;
                return $errors;
            }
            if ($this->provided['city'] && !Validator::notEmpty($this->city)) {
                $errors['city'] = ValidationError::CANNOT_BE_EMPTY->value;
            }
            if ($this->provided['address'] && !Validator::notEmpty($this->address)) {
                $errors['address'] = ValidationError::CANNOT_BE_EMPTY->value;
            }
            if ($this->provided['zip_code'] && !Validator::zipCode($this->zip_code)) {
                $errors['zip_code'] = ValidationError::INVALID_ZIP_CODE->value;
            }
            if ($this->provided['country_code'] && !Validator::countryCode($this->country_code)) {
                $errors['country_code'] = ValidationError::INVALID_COUNTRY_CODE->value;
            }
            if ($this->provided['phone_number'] && !Validator::phone($this->phone_number)) {
                $errors['phone_number'] = ValidationError::INVALID_PHONE->value;
            }
        } else {
            if (!Validator::notEmpty($this->city)) {
                $errors['city'] = ValidationError::REQUIRED->value;
            }
            if (!Validator::notEmpty($this->address)) {
                $errors['address'] = ValidationError::REQUIRED->value;
            }
            if (!Validator::zipCode($this->zip_code)) {
                $errors['zip_code'] = ValidationError::INVALID_ZIP_CODE->value;
            }
            if (!Validator::countryCode($this->country_code)) {
                $errors['country_code'] = ValidationError::INVALID_COUNTRY_CODE->value;
            }
            if (!Validator::phone($this->phone_number)) {
                $errors['phone_number'] = ValidationError::INVALID_PHONE->value;
            }
        }

        return $errors;
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
     * @return array
     */
    public function asArray(): array
    {
        return [
            'city' => $this->city,
            'address' => $this->address,
            'zip_code' => $this->zip_code,
            'country_code' => $this->country_code,
            'phone_number' => $this->phone_number
        ];
    }

    /** Patch array for UPDATE (only provided fields) */
    public function toPatchArray(): array
    {
        $patch = [];
        foreach (array_keys($this->provided) as $k) {
            if ($this->provided[$k]) {
                $patch[$k] = $this->{$k};
            }
        }
        return $patch;
    }
}
