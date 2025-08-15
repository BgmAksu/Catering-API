<?php

namespace App\Enums;

enum ResponseMessages: string
{
     case SUCCESS_EMPLOYEE_UPDATED  = 'Employee updated';
     case SUCCESS_FACILITY_UPDATED  = 'Facility updated';
     case SUCCESS_LOCATION_UPDATED  = 'Location updated';
     case SUCCESS_TAG_UPDATED       = 'Tag updated';
     case NO_CHANGES                = 'No changes';
}
