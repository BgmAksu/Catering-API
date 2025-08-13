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
    /** @var array{city:string,address:string,zip_code:string,country_code:string,phone_number:string} */
    public array $location = [
        'city' => '',
        'address' => '',
        'zip_code' => '',
        'country_code' => '',
        'phone_number' => '',
    ];

    /** @var string[] */
    public array $tags = [];

    /**
     * Build from the array shape returned by repositories/controllers.
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        $self = new self();
        $self->id            = (int)($data['id'] ?? 0);
        $self->name          = (string)($data['name'] ?? '');
        $self->creation_date = (string)($data['creation_date'] ?? '');

        $loc = $data['location'] ?? [];
        $self->location = [
            'city'         => (string)($loc['city'] ?? ''),
            'address'      => (string)($loc['address'] ?? ''),
            'zip_code'     => (string)($loc['zip_code'] ?? ''),
            'country_code' => (string)($loc['country_code'] ?? ''),
            'phone_number' => (string)($loc['phone_number'] ?? ''),
        ];

        $tags = $data['tags'] ?? [];
        $self->tags = array_values(array_filter(array_map('strval', is_array($tags) ? $tags : [])));

        return $self;
    }

    /**
     * Convert back to API response shape (keeps the current contract).
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id'            => $this->id,
            'name'          => $this->name,
            'creation_date' => $this->creation_date,
            'location'      => $this->location,
            'tags'          => $this->tags,
        ];
    }
}
