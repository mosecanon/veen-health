<?php
// Migration 000010: Update default hospital_roles permissions with newly added clinical & diagnostic modules
return [
    'up' => [
        "UPDATE hospital_roles 
         SET permissions = '[\"clinical_emr\",\"consultations\",\"appointments\",\"inpatient\",\"patients\",\"lab_view\",\"reports_view\",\"clinic_kids\",\"clinic_maternal\",\"clinic_ccc\",\"radiology\",\"mortuary\"]', updated_at = NOW() 
         WHERE slug = 'doctor' AND is_system = 1",

        "UPDATE hospital_roles 
         SET permissions = '[\"queue\",\"triage\",\"patients\",\"appointments\",\"inpatient\",\"consultations_view\",\"clinic_kids\",\"clinic_maternal\",\"clinic_ccc\",\"radiology\"]', updated_at = NOW() 
         WHERE slug = 'nurse' AND is_system = 1",

        "UPDATE hospital_roles 
         SET permissions = '[\"clinical_emr\",\"consultations\",\"pharmacy\",\"pharmacy_pos\",\"pharmacy_inventory\",\"lab\",\"laboratory\",\"inpatient\",\"billing\",\"queue\",\"appointments\",\"hr\",\"expenses\",\"reports\",\"reports_view\",\"staff_admin\",\"clinic_kids\",\"clinic_maternal\",\"clinic_ccc\",\"radiology\",\"mortuary\"]', updated_at = NOW() 
         WHERE slug = 'hospital_admin' AND is_system = 1",
    ],
    'down' => [
        "UPDATE hospital_roles 
         SET permissions = '[\"clinical_emr\",\"consultations\",\"appointments\",\"inpatient\",\"patients\",\"lab_view\",\"reports_view\"]', updated_at = NOW() 
         WHERE slug = 'doctor' AND is_system = 1",

        "UPDATE hospital_roles 
         SET permissions = '[\"queue\",\"triage\",\"patients\",\"appointments\",\"inpatient\",\"consultations_view\"]', updated_at = NOW() 
         WHERE slug = 'nurse' AND is_system = 1",

        "UPDATE hospital_roles 
         SET permissions = '[\"clinical_emr\",\"consultations\",\"pharmacy\",\"pharmacy_pos\",\"pharmacy_inventory\",\"lab\",\"laboratory\",\"inpatient\",\"billing\",\"queue\",\"appointments\",\"hr\",\"expenses\",\"reports\",\"reports_view\",\"staff_admin\"]', updated_at = NOW() 
         WHERE slug = 'hospital_admin' AND is_system = 1",
    ]
];
