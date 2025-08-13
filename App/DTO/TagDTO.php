<?php
namespace App\DTO;

use App\Helper\Sanitizer;
use App\Helper\Validator;

/**
 * Data Transfer Object for Tag
 */
class TagDTO
{
    /**
     * @var string
     */
    public string $name;

    public function __construct($name)
    {
        $this->name = Sanitizer::string($name);
    }

    /**
     * Valid when there are no field errors
     * @return bool
     */
    public function isValid(): bool
    {
        return Validator::notEmpty($this->name);
    }
}
