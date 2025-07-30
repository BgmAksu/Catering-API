<?php
namespace App\DTO;

use App\Helper\Sanitizer;
use App\Helper\Validator;

class TagDTO
{
    public string $name;

    public function __construct($name)
    {
        $this->name = Sanitizer::string($name);
    }

    public function isValid(): bool
    {
        return Validator::notEmpty($this->name);
    }
}
