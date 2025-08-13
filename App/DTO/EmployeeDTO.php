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
    public string $name;

    /**
     * @var string
     */
    public string $email;

    /**
     * @var string|array|null
     */
    public string|array|null $phone;

    /**
     * @var string
     */
    public string $position;

    /**
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->name = Sanitizer::string($data['name'] ?? '');
        $this->email = Sanitizer::email($data['email'] ?? '');
        $this->phone = Sanitizer::phone($data['phone'] ?? '');
        $this->position = Sanitizer::string($data['position'] ?? '');
    }

    /**
     * Valid when there are no field errors
     * @return bool
     */
    public function isValid(): bool
    {
        return
            Validator::notEmpty($this->name) &&
            Validator::email($this->email) &&
            Validator::phone($this->phone) &&
            Validator::notEmpty($this->position);
    }

    /**
     * @return array
     */
    public function asArray(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'position' => $this->position
        ];
    }
}