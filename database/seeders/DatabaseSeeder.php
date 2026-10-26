<?php
/**
 * Demo data: countries, plans, Veen super admin, two hospitals in different countries.
 * Run: php veen db:seed   (or php veen migrate:fresh --seed)
 */

use App\Models\Patient;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\ProductCategory;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Services\Sequence;
use App\Services\TenantProvisioner;
use Veen\Core\Database\DB;
use Veen\Core\Tenancy\TenantManager;

return function (callable $say) {
    // ---------- Countries ----------
    $countries = [
        ['KE', 'Kenya', 'KES', '+254', 'Africa/Nairobi', 'sw', 16, 'VAT', ['cash', 'mpesa', 'airtel_money', 'card', 'insurance']],
        ['UG', 'Uganda', 'UGX', '+256', 'Africa/Kampala', 'en', 18, 'VAT', ['cash', 'mtn_momo', 'airtel_money', 'card', 'insurance']],
        ['TZ', 'Tanzania', 'TZS', '+255', 'Africa/Dar_es_Salaam', 'sw', 18, 'VAT', ['cash', 'mpesa', 'airtel_money', 'card', 'insurance']],
        ['RW', 'Rwanda', 'RWF', '+250', 'Africa/Kigali', 'en', 18, 'VAT', ['cash', 'mtn_momo', 'airtel_money', 'card', 'insurance']],
        ['NG', 'Nigeria', 'NGN', '+234', 'Africa/Lagos', 'en', 7.5, 'VAT', ['cash', 'paystack', 'flutterwave', 'card', 'insurance']],
        ['GH', 'Ghana', 'GHS', '+233', 'Africa/Accra', 'en', 15, 'VAT', ['cash', 'mtn_momo', 'paystack', 'card', 'insurance']],
        ['ZA', 'South Africa', 'ZAR', '+27', 'Africa/Johannesburg', 'en', 15, 'VAT', ['cash', 'card', 'insurance']],
        ['ET', 'Ethiopia', 'ETB', '+251', 'Africa/Addis_Ababa', 'en', 15, 'VAT', ['cash', 'card', 'insurance']],
    ];
    foreach ($countries as [$code, $name, $cur, $phone, $tz, $loc, $tax, $taxName, $methods]) {
        DB::statement(
            'INSERT INTO countries (code, name, currency, phone_code, timezone, locale, default_tax_rate, tax_name, payment_methods) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE name = VALUES(name)',
            [$code, $name, $cur, $phone, $tz, $loc, $tax, $taxName, json_encode($methods)]
        );
    }
    $say('  ✔ Countries');

    // ---------- Plans ----------
    $plans = [
        ['clinic', 'Clinic', 4900, 10, 1, ['patients', 'queue', 'pharmacy']],
        ['hospital', 'Hospital', 14900, 75, 3, ['patients', 'queue', 'pharmacy', 'lab', 'ipd', 'billing']],
        ['enterprise', 'Enterprise', 39900, null, null, ['*']],
    ];
    foreach ($plans as [$code, $name, $price, $users, $branches, $features]) {
        DB::statement(
            'INSERT INTO plans (code, name, price_usd_cents, max_users, max_branches, features, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())',
            [$code, $name, $price, $users, $branches, json_encode($features)]
        );
    }
    $say('  ✔ Plans');

    // ---------- Veen super admin ----------
    DB::statement(
        "INSERT INTO users (tenant_id, name, email, password, role, title, is_active, created_at, updated_at) VALUES (NULL, 'Veen Platform Admin', 'admin@veen.africa', ?, 'super_admin', 'Platform Administrator', 1, NOW(), NOW())",
        [password_hash('Password@123', PASSWORD_DEFAULT)]
    );
    $say('  ✔ Super admin: admin@veen.africa / Password@123');

    // ---------- Demo hospitals ----------
    $hospitals = [
        ['Nairobi Care Hospital', 'nairobicare', 'KE', 'nairobicare.co.ke', [
            ['Dr. Achieng Otieno', 'doctor', 'Consultant Physician'], ['Dr. Kamau Mwangi', 'doctor', 'Medical Officer'],
            ['Wanjiru Njeri', 'nurse', 'Triage Nurse'], ['Faith Chebet', 'receptionist', 'Front Desk'],
            ['Brian Odhiambo', 'pharmacist', 'Pharmacist'], ['Mercy Akinyi', 'cashier', 'Cashier'], ['Peter Kiprono', 'lab_tech', 'Lab Technologist'],
        ], [['Grace', 'Wambui', 'female', '1988-04-12', '+254712345678'], ['John', 'Mutua', 'male', '1975-09-30', '+254722111222'], ['Amina', 'Hassan', 'female', '2001-01-05', '+254733444555'], ['David', 'Kariuki', 'male', '1962-11-19', '+254701999888']]],
        ['Lagos Prime Medical Centre', 'lagosprime', 'NG', 'lagosprime.com.ng', [
            ['Dr. Chukwuemeka Obi', 'doctor', 'Consultant'], ['Ngozi Adeyemi', 'nurse', 'Nurse'], ['Tunde Bakare', 'receptionist', 'Front Desk'],
            ['Folake Johnson', 'pharmacist', 'Pharmacist'], ['Ibrahim Musa', 'cashier', 'Cashier'],
        ], [['Adaeze', 'Okafor', 'female', '1990-06-21', '+2348031234567'], ['Emeka', 'Nwosu', 'male', '1983-03-14', '+2348059876543'], ['Fatima', 'Bello', 'female', '1995-12-02', '+2348021112233']]],
    ];

    $products = [
        // name, generic, barcode, form, strength, schedule, price (major units), tax, category
        ['Panadol Extra', 'Paracetamol + Caffeine', '6001234500011', 'tablet', '500mg/65mg', 'otc', 25, 0, 'Analgesics'],
        ['Brufen', 'Ibuprofen', '6001234500028', 'tablet', '400mg', 'otc', 15, 0, 'Analgesics'],
        ['Amoxil', 'Amoxicillin', '6001234500035', 'capsule', '500mg', 'pom', 40, 0, 'Antibiotics'],
        ['Augmentin', 'Amoxicillin + Clavulanate', '6001234500042', 'tablet', '625mg', 'pom', 120, 0, 'Antibiotics'],
        ['Coartem', 'Artemether + Lumefantrine', '6001234500059', 'tablet', '20/120mg', 'pom', 350, 0, 'Antimalarials'],
        ['Benylin Dry Cough', 'Dextromethorphan', '6001234500066', 'syrup', '100ml', 'otc', 450, 16, 'Cough & Cold'],
        ['Vitamin C Effervescent', 'Ascorbic Acid', '6001234500073', 'tablet', '1000mg', 'otc', 380, 16, 'Vitamins & Supplements'],
        ['ORS Sachet', 'Oral Rehydration Salts', '6001234500080', 'powder', '20.5g', 'otc', 30, 0, 'First Aid'],
        ['Elastoplast Strips', 'Adhesive bandage', '6001234500097', 'strip', '20 pcs', 'otc', 250, 16, 'First Aid'],
        ['Metformin', 'Metformin HCl', '6001234500103', 'tablet', '500mg', 'pom', 10, 0, 'Chronic Care'],
        ['Amlodipine', 'Amlodipine', '6001234500110', 'tablet', '5mg', 'pom', 12, 0, 'Chronic Care'],
        ['Hand Sanitizer', 'Ethanol 70%', '6001234500127', 'gel', '500ml', 'otc', 600, 16, 'Personal Care'],
        ['Tramadol', 'Tramadol HCl', '6001234500134', 'capsule', '50mg', 'controlled', 35, 0, 'Analgesics'],
    ];

    foreach ($hospitals as [$name, $slug, $cc, $domain, $staff, $patients]) {
        $tenant = TenantProvisioner::provision([
            'name' => $name, 'slug' => $slug, 'country_code' => $cc, 'plan_id' => 2, 'status' => 'active',
            'email' => "info@$domain", 'phone' => null,
            'admin_name' => 'Hospital Administrator', 'admin_email' => "admin@$domain", 'admin_password' => 'Password@123',
        ]);

        TenantManager::run($tenant, function () use ($tenant, $staff, $domain, $patients, $products, $cc) {
            $branchId = (int) DB::selectOne('SELECT id FROM branches WHERE tenant_id = ? AND is_main = 1', [$tenant['id']])['id'];

            foreach ($staff as [$sName, $role, $title]) {
                $first = strtolower(preg_replace('/[^a-z]/i', '', explode(' ', str_replace('Dr. ', '', $sName))[0]));
                DB::statement(
                    'INSERT INTO users (tenant_id, branch_id, name, email, password, role, title, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, 1, NOW(), NOW())',
                    [$tenant['id'], $branchId, $sName, "$first@$domain", password_hash('Password@123', PASSWORD_DEFAULT), $role, $title]
                );
            }

            foreach ($patients as [$f, $l, $sex, $dob, $phone]) {
                $p = new Patient();
                $p->fill(['branch_id' => $branchId, 'first_name' => $f, 'last_name' => $l, 'sex' => $sex, 'dob' => $dob, 'phone' => $phone])
                  ->forceFill(['mrn' => Sequence::mrn()])->save();
            }

            $supplier = Supplier::create(['name' => $cc === 'KE' ? 'MedSource Kenya Ltd' : 'PharmaHub Nigeria Ltd', 'phone' => null]);
            $cats = [];
            foreach (ProductCategory::all() as $c) $cats[$c->name] = $c->id;
            // NGN prices scaled up for realism
            $scale = $cc === 'NG' ? 10 : 1;

            foreach ($products as $i => [$pn, $gen, $bc, $form, $str, $sch, $price, $tax, $cat]) {
                $prod = Product::create([
                    'category_id' => $cats[$cat] ?? null, 'name' => $pn, 'generic_name' => $gen, 'barcode' => $bc, 'sku' => 'SKU' . (1000 + $i),
                    'form' => $form, 'strength' => $str, 'unit' => $form === 'syrup' || $form === 'gel' ? 'bottle' : 'pcs', 'schedule' => $sch,
                    'price_minor' => to_minor($price * $scale, $tenant['currency']), 'tax_rate' => $tax, 'reorder_level' => 20, 'is_active' => 1,
                ]);
                // Two batches with different expiries to demonstrate FEFO
                $batches = [[random_int(15, 60), '+' . random_int(2, 5) . ' months'], [random_int(80, 200), '+' . random_int(10, 24) . ' months']];
                $total = 0;
                foreach ($batches as $j => [$qty, $exp]) {
                    $b = ProductBatch::create([
                        'product_id' => $prod->id, 'supplier_id' => $supplier->id, 'batch_no' => 'B' . date('y') . str_pad((string) ($i * 10 + $j), 4, '0', STR_PAD_LEFT),
                        'expiry_date' => date('Y-m-d', strtotime($exp)), 'cost_minor' => (int) round($prod->price_minor * 0.6),
                        'qty_received' => $qty, 'qty_on_hand' => $qty, 'received_at' => now(),
                    ]);
                    StockMovement::create(['product_id' => $prod->id, 'batch_id' => $b->id, 'type' => 'receive', 'qty' => $qty, 'reference_type' => 'batch', 'reference_id' => $b->id, 'note' => 'Opening stock', 'created_at' => now()]);
                    $total += $qty;
                }
                Product::query()->where('id', $prod->id)->update(['stock_qty' => $total]);
            }
        });
        $say("  ✔ Hospital: $name  (admin@$domain / Password@123)");
    }

    (require __DIR__ . '/ClinicalSeeder.php')($say);
};
