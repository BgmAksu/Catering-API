<?php

namespace App\Enums;

/**
 * Validation error codes for DTOs.
 */
enum ValidationError: string
{
    case REQUIRED                      = 'required';
    case CANNOT_BE_EMPTY               = 'cannot_be_empty';
    case INVALID                       = 'invalid';
    case AT_LEAST_ONE_FIELD_REQUIRED   = 'at_least_one_field_required';
    case INVALID_EMAIL                 = 'invalid_email';
    case INVALID_PHONE                 = 'invalid_phone';
    case INVALID_ZIP_CODE              = 'invalid_zip_code';
    case INVALID_COUNTRY_CODE          = 'invalid_country_code';
    case MUST_BE_ARRAY_OF_STRINGS      = 'must_be_array_of_strings';
}