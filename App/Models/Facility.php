<?php

namespace App\Models;

/**
 * Simple Facility model representing DB row
 */
class Facility
{
    /**
     * @var int
     */
    public int $id;
    /**
     * @var string
     */
    public string $name;
    /**
     * @var string
     */
    public string $creation_date;
    /**
     * @var array{city:string,address:string,zip_code:string,country_code:string,phone_number:string}
     */
    public array $location = [];
    /**
     * s@var string[]
     */
    public array $tags = [];
}
