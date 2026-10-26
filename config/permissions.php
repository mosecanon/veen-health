<?php
/**
 * Role-based permissions. Use "*" for everything, "module.*" for a module.
 */
return [
    'labels' => [
        'super_admin'    => 'Veen Super Admin',
        'hospital_admin' => 'Hospital Admin',
        'doctor'         => 'Doctor',
        'nurse'          => 'Nurse',
        'receptionist'   => 'Receptionist',
        'cashier'        => 'Cashier',
        'pharmacist'     => 'Pharmacist',
        'lab_tech'       => 'Lab Technician',
    ],
    'roles' => [
        'super_admin'    => ['platform.*'],
        'hospital_admin' => ['*'],
        'doctor'         => ['patients.view', 'patients.manage', 'queue.serve'],
        'nurse'          => ['patients.view', 'patients.manage', 'queue.serve', 'queue.issue'],
        'receptionist'   => ['patients.view', 'patients.manage', 'queue.issue'],
        'cashier'        => ['patients.view', 'queue.serve', 'pharmacy.sell'],
        'pharmacist'     => ['patients.view', 'queue.serve', 'pharmacy.sell', 'pharmacy.manage'],
        'lab_tech'       => ['patients.view', 'queue.serve'],
    ],
];
