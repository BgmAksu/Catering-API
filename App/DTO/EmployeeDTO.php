<?php

namespace App\DTO;

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
    public string $phone_number = '';

    /**
     * @var string
     */
    public string $title = '';

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
        'phone_number' => false,
        'title' => false,
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

        $phoneProvided = array_key_exists('phone_number', $data) || array_key_exists('phone', $data);
        $titleProvided = array_key_exists('title', $data) || array_key_exists('position', $data);

        $this->provided['phone_number'] = $phoneProvided;
        $this->provided['title']        = $titleProvided;

        $this->name         = Sanitizer::string($data['name']  ?? '');
        $this->email        = Sanitizer::string($data['email'] ?? '');
        $this->phone_number = Sanitizer::string($data['phone_number'] ?? ($data['phone'] ?? ''));
        $this->title        = Sanitizer::string($data['title'] ?? ($data['position'] ?? ''));
    }

    /**
     * True if this DTO is used for update (partial)
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
        return !$this->provided['name']
            && !$this->provided['email']
            && !$this->provided['phone_number']
            && !$this->provided['title'];
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
                $errors['payload'] = 'at_least_one_field_required';
                return $errors;
            }

            if ($this->provided['name'] && !Validator::notEmpty($this->name)) {
                $errors['name'] = 'cannot_be_empty';
            }
            if ($this->provided['email'] && !Validator::email($this->email)) {
                $errors['email'] = 'invalid';
            }
            if ($this->provided['phone_number'] && !Validator::phone($this->phone_number)) {
                $errors['phone_number'] = 'invalid';
            }
            if ($this->provided['title'] && !Validator::notEmpty($this->title)) {
                $errors['title'] = 'cannot_be_empty';
            }
        } else {
            // CREATE: all required fields must be present & valid
            if (!Validator::notEmpty($this->name)) {
                $errors['name'] = 'required';
            }
            if (!Validator::email($this->email)) {
                $errors['email'] = 'invalid';
            }
            if (!Validator::phone($this->phone_number)) {
                $errors['phone_number'] = 'invalid';
            }
            if (!Validator::notEmpty($this->title)) {
                $errors['title'] = 'required';
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
            'phone_number' => $this->phone_number,
            'title'        => $this->title,
        ];
    }

    /**
     * Patch array for UPDATE (only provided fields).
     * @return array<string,string>
     */
    public function toPatchArray(): array
    {
        $patch = [];
        foreach (['name','email','phone_number','title'] as $k) {
            if ($this->provided[$k]) {
                $patch[$k] = $this->{$k};
            }
        }
        return $patch;
    }

    /**
     * Helper to check if a field was sent in the payload
     * @param string $field
     * @return bool
     */
    public function provided(string $field): bool
    {
        return (bool)($this->provided[$field] ?? false);
    }
}