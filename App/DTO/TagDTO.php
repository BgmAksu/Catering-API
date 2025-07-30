<?php
namespace App\DTO;

use App\Helper\Sanitizer;
use App\Helper\Validator;

class TagDTO
{
    public $name;

    public function __construct($name)
    {
        $this->name = Sanitizer::string($name);
    }

    public function isValid()
    {
        return Validator::notEmpty($this->name);
    }
}
