<?php

namespace App\Models;

/**
 * Simple Location model
 */
class Location
{
    /** Raw row as returned by repository (preserved for response shape) */
    private array $raw = [];

    /**
     * @var int
     */
    public int $id = 0;
    /**
     * @var string
     */
    public string $city = '';
    /**
     * @var string
     */
    public string $address = '';
    /**
     * @var string
     */
    public string $zip_code = '';
    /**
     * @var string
     */
    public string $country_code = '';
    /**
     * @var string
     */
    public string $phone_number = '';

    /**
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        $self = new self();
        $self->raw = $data;

        $self->id            = isset($data['id']) ? (int)$data['id'] : 0;
        $self->city          = (string)($data['city'] ?? '');
        $self->address       = (string)($data['address'] ?? '');
        $self->zip_code      = (string)($data['zip_code'] ?? '');
        $self->country_code  = (string)($data['country_code'] ?? '');
        $self->phone_number  = (string)($data['phone_number'] ?? '');

        return $self;
    }

    /**
     * Keep API response contract by filling missing keys from typed fields.
     * @return array
     */
    public function toArray(): array
    {
        foreach (['id','city','address','zip_code','country_code','phone_number'] as $k) {
            if (!isset($this->raw[$k])) {
                $this->raw[$k] = $this->{$k};
            }
        }
        return $this->raw;
    }
}