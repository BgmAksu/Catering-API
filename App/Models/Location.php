<?php

namespace App\Models;

/**
 * Simple Location model
 */
class Location
{
    /**
     * @var int
     */
    public int $id;
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
     * @var string
     */
    public string $phone_number;
}