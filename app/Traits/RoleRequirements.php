<?php

namespace App\Traits;

trait RoleRequirements
{
    public function rolesThatMustHave($tireLevel)
    {
        switch ($tireLevel) {
            case 1:
                return ['sudo'];
                break;
            case 2:
                return ['admin', 'sudo'];
                break;
            case 3:
                return ['admin', 'sudo', 'landlord'];
                break;
            case 4:
                return ['admin', 'sudo', 'landlord', 'agent'];
                    break;
            case 5:
                return ['admin', 'sudo', 'landlord', 'agent', 'tenant'];
                    break;
            default:
                return [];
        }
    }
}
