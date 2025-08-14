<?php

namespace App\Models;

/**
 * Simple Employee model.
 */
class Employee
{
    /** Raw row as returned by repository (preserved for response shape) */
    private array $raw = [];

    public ?int $id = null;
    public ?int $facility_id = null;
    public ?string $name = null;
    public ?string $email = null;
    public ?string $phone_number = null;
    public ?string $title = null;
    public ?string $created_at = null;

    /**
     * Build from the array shape returned by repositories/controllers.
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        $self = new self();
        $self->raw = $data;

        $self->id = isset($data['id']) ? (int)$data['id'] : null;
        $self->facility_id = isset($data['facility_id']) ? (int)$data['facility_id'] : null;

        if (isset($data['name'])) {
            $self->name = (string)$data['name'];
        } else {
            $first = isset($data['first_name']) ? (string)$data['first_name'] : '';
            $last  = isset($data['last_name']) ? (string)$data['last_name'] : '';
            $full  = trim($first . ' ' . $last);
            $self->name = $full !== '' ? $full : null;
        }

        $self->email = isset($data['email']) ? (string)$data['email'] : null;

        if (isset($data['phone_number'])) {
            $self->phone_number = (string)$data['phone_number'];
        } elseif (isset($data['phone'])) {
            $self->phone_number = (string)$data['phone'];
        }

        if (isset($data['title'])) {
            $self->title = (string)$data['title'];
        } elseif (isset($data['position'])) {
            $self->title = (string)$data['position'];
        }

        if (isset($data['created_at'])) {
            $self->created_at = (string)$data['created_at'];
        } elseif (isset($data['creation_date'])) {
            $self->created_at = (string)$data['creation_date'];
        }

        return $self;
    }

    /**
     * Convert back to API response shape.
     * @return array
     */
    public function toArray(): array
    {
        // Optionally keep derived name in the raw array if not set
        if (!isset($this->raw['name']) && $this->name !== null) {
            $this->raw['name'] = $this->name;
        }
        if (!isset($this->raw['phone_number']) && $this->phone_number !== null) {
            $this->raw['phone_number'] = $this->phone_number;
        }
        if (!isset($this->raw['title']) && $this->title !== null) {
            $this->raw['title'] = $this->title;
        }
        if (!isset($this->raw['created_at']) && $this->created_at !== null) {
            $this->raw['created_at'] = $this->created_at;
        }
        return $this->raw;
    }
}
