<?php
namespace App\Models;

use Veen\Core\Database\Model;

class RadiologyOrder extends Model
{
    protected static string $table = 'radiology_orders';
    protected static array $fillable = [
        'branch_id', 'patient_id', 'admission_id', 'order_number', 'doctor_user_id',
        'modality', 'body_part', 'contrast_type', 'clinical_indication',
        'provisional_diagnosis', 'has_pacemaker', 'has_metallic_implants',
        'has_aneurysm_clips', 'has_claustrophobia', 'egfr_renal_value',
        'sedation_required', 'safety_checklist_cleared', 'scheduled_at',
        'transport_status', 'porter_notes', 'radiologist_user_id',
        'radiologist_name', 'findings', 'impression', 'priority', 'status',
        'completed_at', 'cost',
    ];
}
