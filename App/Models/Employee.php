<?php

namespace App\Models;

/**
 * Simple Employee model.
 */
class Employee
{
    /** Raw row as returned by repository (preserved for response shape) */
    private array $raw = [];

    /**
     * @var int|null
     */
    public ?int $id = null;
    /**
     * @var int|null
     */
    public ?int $facility_id = null;
    /**
     * @var string|null
     */
    public ?string $name = null;
    /**
     * @var string|null
     */
    public ?string $email = null;
    /**
     * @var string|null
     */
    public ?string $phone = null;
    /**
     * @var string|null
     */
    public ?string $position = null;

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
        $self->name = isset($data['name']) ? (string)$data['name'] : null;
        $self->email = isset($data['email']) ? (string)$data['email'] : null;
        $self->phone = isset($data['phone']) ? (string)$data['phone'] : null;
        $self->position = isset($data['position']) ? (string)$data['position'] : null;

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
        if (!isset($this->raw['email']) && $this->email !== null) {
            $this->raw['email'] = $this->email;
        }
        if (!isset($this->raw['phone']) && $this->phone !== null) {
            $this->raw['phone'] = $this->phone;
        }
        if (!isset($this->raw['position']) && $this->position !== null) {
            $this->raw['position'] = $this->position;
        }

        return $this->raw;
    }
}
