<?php

namespace App\DTO;

use App\Enums\ValidationError;
use App\Helper\Sanitizer;
use App\Helper\Validator;

/**
 * Data Transfer Object for Employee
 */
class EmployeeDTO
{
    /**
     * @var string
     */
    public string $name = '';

    /**
     * @var string
     */
    public string $email = '';

    /**
     * @var string
     */
    public string $phone = '';

    /**
     * @var string
     */
    public string $position = '';

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
        'email' => false,
        'phone' => false,
        'position' => false,
    ];

    /**
     * @param array $data Raw request body
     * @param bool $isUpdate If true, performs partial validation
     */
    public function __construct(array $data, bool $isUpdate = false)
    {
        $this->isUpdate = $isUpdate;

        $this->provided['name']  = array_key_exists('name', $data);
        $this->provided['email'] = array_key_exists('email', $data);
        $this->provided['phone'] = array_key_exists('phone', $data);
        $this->provided['position'] = array_key_exists('position', $data);

        $this->name = Sanitizer::string($data['name']  ?? '');
        $this->email = Sanitizer::string($data['email'] ?? '');
        $this->phone = Sanitizer::string($data['phone'] ?? '');
        $this->position = Sanitizer::string($data['position'] ?? '');
    }

    /**
     * True when update payload contains no updatable fields at all
     * @return bool
     */
    public function isEmptyPayload(): bool
    {
        return !$this->provided['name']
            && !$this->provided['email']
            && !$this->provided['phone']
            && !$this->provided['position'];
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

        if ($this->isUpdate) {
            // Block completely empty update payload
            if ($this->isEmptyPayload()) {
                $errors['payload'] = ValidationError::AT_LEAST_ONE_FIELD_REQUIRED->value;
                return $errors;
            }

            if ($this->provided['name'] && !Validator::notEmpty($this->name)) {
                $errors['name'] = ValidationError::CANNOT_BE_EMPTY->value;
            }
            if ($this->provided['email'] && !Validator::email($this->email)) {
                $errors['email'] = ValidationError::INVALID_EMAIL->value;
            }
            if ($this->provided['phone'] && !Validator::phone($this->phone)) {
                $errors['phone'] = ValidationError::INVALID_PHONE->value;
            }
            if ($this->provided['position'] && !Validator::notEmpty($this->position)) {
                $errors['position'] = ValidationError::CANNOT_BE_EMPTY->value;
            }
        } else {
            // CREATE: all required fields must be present & valid
            if (!Validator::notEmpty($this->name)) {
                $errors['name'] = ValidationError::REQUIRED->value;
            }
            if (!Validator::email($this->email)) {
                $errors['email'] = ValidationError::INVALID_EMAIL->value;
            }
            if (!Validator::phone($this->phone)) {
                $errors['phone'] = ValidationError::INVALID_PHONE->value;
            }
            if (!Validator::notEmpty($this->position)) {
                $errors['position'] = ValidationError::REQUIRED->value;
            }
        }

        return $errors;
    }

    /**
     * Normalized array for CREATE (full set).
     * @return array{name:string,email:string,phone_number:string,title:string}
     */
    public function asArray(): array
    {
        return [
            'name'         => $this->name,
            'email'        => $this->email,
            'phone'        => $this->phone,
            'position'     => $this->position,
        ];
    }

    /**
     * Patch array for UPDATE (only provided fields).
     * @return array<string,string>
     */
    public function toPatchArray(): array
    {
        $patch = [];
        foreach (['name','email','phone','position'] as $k) {
            if ($this->provided[$k]) {
                $patch[$k] = $this->{$k};
            }
        }
        return $patch;
    }
}