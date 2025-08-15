<?php

namespace App\Enums;

enum ResponseErrors: string
{
    case ERROR_EMPLOYEE_NOT_FOUND           = 'Employee not found';
    case ERROR_VALIDATION_FAILED            = 'Validation failed';
    case ERROR_FACILITY_NOT_FOUND           = 'Facility not found';
    case ERROR_CREATION_FAILED_FACILITY     = 'Unexpected error during facility creation';
    case  ERROR_LOCATION_NOT_FOUND          = 'Location not found';
    case ERROR_LOCATION_DELETE              = 'Location not found or used by a facility';
    case ERROR_TAG_NOT_FOUND                = 'Tag not found';
    case ERROR_TAG_MUST_BE_UNIQUE           = 'Tag name must be unique';
    case ERROR_TAG_DELETE                   = 'Tag not found or used by a facility';
}
