<?php

namespace App\Models;

/**
 * Simple Tag model
 */
class Tag
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
    public string $name = '';

    /**
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        $self = new self();
        $self->raw = $data;

        $self->id   = isset($data['id']) ? (int)$data['id'] : 0;
        $self->name = isset($data['name']) ? (string)$data['name'] : '';

        return $self;
    }

    /**
     * Keep API response contract by filling missing keys from typed fields.
     * @return array
     */
    public function toArray(): array
    {
        if (!isset($this->raw['id']))   { $this->raw['id'] = $this->id; }
        if (!isset($this->raw['name'])) { $this->raw['name'] = $this->name; }
        return $this->raw;
    }
}