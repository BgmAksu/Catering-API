<?php

namespace App\DTO;

use App\Helper\Sanitizer;
use App\Helper\Validator;

class EmployeeDTO
{
    public $name;
    public $email;
    public $phone;
    public $position;

    public function __construct(array $data)
    {
        $this->name = Sanitizer::string($data['name'] ?? '');
        $this->email = Sanitizer::email($data['email'] ?? '');
        $this->phone = Sanitizer::phone($data['phone'] ?? '');
        $this->position = Sanitizer::string($data['position'] ?? '');
    }

    public function isValid()
    {
        return
            Validator::notEmpty($this->name) &&
            Validator::email($this->email) &&
            Validator::phone($this->phone) &&
            Validator::notEmpty($this->position);
    }

    public function asArray()
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'position' => $this->position
        ];
    }
}