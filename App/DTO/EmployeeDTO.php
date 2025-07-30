<?php

namespace App\DTO;

use App\Helper\Sanitizer;
use App\Helper\Validator;

class EmployeeDTO
{
    public string $name;
    public string $email;
    public string|array|null $phone;
    public string $position;

    public function __construct(array $data)
    {
        $this->name = Sanitizer::string($data['name'] ?? '');
        $this->email = Sanitizer::email($data['email'] ?? '');
        $this->phone = Sanitizer::phone($data['phone'] ?? '');
        $this->position = Sanitizer::string($data['position'] ?? '');
    }

    public function isValid(): bool
    {
        return
            Validator::notEmpty($this->name) &&
            Validator::email($this->email) &&
            Validator::phone($this->phone) &&
            Validator::notEmpty($this->position);
    }

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