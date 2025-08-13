<?php
namespace App\DTO;

use App\Helper\Sanitizer;
use App\Helper\Validator;

/**
 * Data Transfer Object for Location
 */
class LocationDTO
{
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
     * @var string|array|null
     */
    public string|array|null $phone_number;

    public function __construct(array $data)
    {
        $this->city = Sanitizer::string($data['city'] ?? '');
        $this->address = Sanitizer::string($data['address'] ?? '');
        $this->zip_code = Sanitizer::zipCode($data['zip_code'] ?? '');
        $this->country_code = Sanitizer::countryCode($data['country_code'] ?? '');
        $this->phone_number = Sanitizer::phone($data['phone_number'] ?? '');
    }

    /**
     * Valid when there are no field errors
     * @return bool
     */
    public function isValid(): bool
    {
        return
            Validator::notEmpty($this->city) &&
            Validator::notEmpty($this->address) &&
            Validator::zipCode($this->zip_code) &&
            Validator::countryCode($this->country_code) &&
            Validator::phone($this->phone_number);
    }

    /**
     * @return array
     */
    public function asArray(): array
    {
        return [
            'city' => $this->city,
            'address' => $this->address,
            'zip_code' => $this->zip_code,
            'country_code' => $this->country_code,
            'phone_number' => $this->phone_number
        ];
    }
}
