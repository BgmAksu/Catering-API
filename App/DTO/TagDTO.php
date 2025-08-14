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
    public string $name = '';

    /** Whether this DTO is used for update (partial) */
    private bool $isUpdate = false;

    /** Tracks which fields were provided */
    private array $provided = [
        'name' => false,
    ];

    public function __construct(array $data, bool $isUpdate = false)
    {
        $this->isUpdate = $isUpdate;
        $this->provided['name'] = array_key_exists('name', $data);
        $this->name = Sanitizer::string($data['name'] ?? '');
    }

    /** True when update payload contains no updatable fields */
    public function isEmptyPayload(): bool
    {
        return !$this->provided['name'];
    }

    /** Field-level validation errors */
    public function errors(): array
    {
        $errors = [];

        if ($this->isUpdate) {
            if ($this->isEmptyPayload()) {
                $errors['payload'] = 'at_least_one_field_required';
                return $errors;
            }
            if ($this->provided['name'] && !Validator::notEmpty($this->name)) {
                $errors['name'] = 'cannot_be_empty';
            }
        } else {
            if (!Validator::notEmpty($this->name)) {
                $errors['name'] = 'required';
            }
        }

        return $errors;
    }

    public function isValid(): bool
    {
        return empty($this->errors());
    }

    /** Patch array for UPDATE (only provided fields). */
    public function toPatchArray(): array
    {
        $patch = [];
        if ($this->provided['name']) {
            $patch['name'] = $this->name;
        }
        return $patch;
    }
}
