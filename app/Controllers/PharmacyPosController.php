<?php
namespace App\Controllers;

use App\Models\CashSession;
use App\Models\Patient;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\StockMovement;
use App\Services\Sequence;
use Veen\Core\Auth;
use Veen\Core\Database\DB;
use Veen\Core\HttpException;
use Veen\Core\Request;
use Veen\Core\Response;
use Veen\Core\Tenancy\TenantManager;

class PharmacyPosController extends Controller
{
    public function index(Request $request): Response
    {
        $tenantId = TenantManager::id();
        $tenant = TenantManager::current();

        // Check active cash session for current user
        $session = DB::selectOne(
            "SELECT * FROM cash_sessions WHERE tenant_id = ? AND user_id = ? AND status = 'open' LIMIT 1",
            [$tenantId, Auth::id()]
        );

        // Fetch categories for fast quick-tap buttons
        $categories = DB::select('SELECT * FROM product_categories WHERE tenant_id = ? ORDER BY name ASC', [$tenantId]);

        // Top 20 OTC items ready to click
        $topProducts = DB::select(
            "SELECT p.*, c.name AS category_name,
                    (SELECT b.batch_no FROM product_batches b WHERE b.product_id = p.id AND b.qty_on_hand > 0 AND (b.status = 'approved' OR b.status IS NULL) ORDER BY b.expiry_date ASC LIMIT 1) AS fefo_batch_no,
                    (SELECT b.expiry_date FROM product_batches b WHERE b.product_id = p.id AND b.qty_on_hand > 0 AND (b.status = 'approved' OR b.status IS NULL) ORDER BY b.expiry_date ASC LIMIT 1) AS fefo_expiry
             FROM products p
             LEFT JOIN product_categories c ON c.id = p.category_id
             WHERE p.tenant_id = ? AND p.is_active = 1 AND p.stock_qty > 0
             ORDER BY p.name ASC LIMIT 24",
            [$tenantId]
        );

        // Country payment methods
        $country = DB::selectOne('SELECT * FROM countries WHERE code = ?', [$tenant['country_code'] ?? 'KE']);
        $paymentMethods = json_decode((string) ($country['payment_methods'] ?? '["cash","mpesa","card"]'), true) ?: ['cash', 'mpesa', 'card'];

        // Preselected patient if referred from Doctor Consultation or direct EMR link
        $rawPatient = trim((string) $request->query('patient_id', ''));
        $selectedPatient = null;
        if ($rawPatient !== '') {
            $isUuid = strlen($rawPatient) === 36 && str_contains($rawPatient, '-');
            $selectedPatient = $isUuid
                ? DB::selectOne(
                    "SELECT p.*, TIMESTAMPDIFF(YEAR, p.dob, CURDATE()) AS age 
                     FROM patients p 
                     WHERE p.uuid = ? AND p.tenant_id = ? AND p.deleted_at IS NULL",
                    [$rawPatient, $tenantId]
                )
                : DB::selectOne(
                    "SELECT p.*, TIMESTAMPDIFF(YEAR, p.dob, CURDATE()) AS age 
                     FROM patients p 
                     WHERE p.id = ? AND p.tenant_id = ? AND p.deleted_at IS NULL",
                    [(int)$rawPatient, $tenantId]
                );
        }

        return view('pharmacy.pos', [
            'title' => 'Over-The-Counter (OTC) Pharmacy POS',
            'session' => $session,
            'categories' => $categories,
            'topProducts' => $topProducts,
            'paymentMethods' => $paymentMethods,
            'tenant' => $tenant,
            'country' => $country,
            'selectedPatient' => $selectedPatient,
        ]);
    }

    public function searchProducts(Request $request): Response
    {
        $tenantId = TenantManager::id();
        $q = trim((string) $request->query('q', ''));
        $catId = (int) $request->query('category_id', 0);

        $params = [$tenantId];
        $where = 'WHERE p.tenant_id = ? AND p.is_active = 1';

        if ($q !== '') {
            $where .= ' AND (p.name LIKE ? OR p.generic_name LIKE ? OR p.barcode LIKE ? OR p.sku LIKE ?)';
            $search = '%' . $q . '%';
            array_push($params, $search, $search, $search, $search);
        }
        if ($catId > 0) {
            $where .= ' AND p.category_id = ?';
            $params[] = $catId;
        }

        $products = DB::select(
            "SELECT p.id, p.name, p.generic_name, p.barcode, p.sku, p.form, p.strength, p.unit,
                    p.schedule, p.price_minor, p.tax_rate, p.stock_qty,
                    c.name AS category_name,
                    (SELECT b.batch_no FROM product_batches b WHERE b.product_id = p.id AND b.qty_on_hand > 0 ORDER BY b.expiry_date ASC LIMIT 1) AS fefo_batch_no,
                    (SELECT b.expiry_date FROM product_batches b WHERE b.product_id = p.id AND b.qty_on_hand > 0 ORDER BY b.expiry_date ASC LIMIT 1) AS fefo_expiry
             FROM products p
             LEFT JOIN product_categories c ON c.id = p.category_id
             $where
             ORDER BY p.name ASC LIMIT 30",
            $params
        );

        return Response::json($products);
    }

    public function checkout(Request $request): Response
    {
        $tenantId = TenantManager::id();
        $tenant = TenantManager::current();

        $input = $request->all();
        $items = $input['items'] ?? [];
        $payments = $input['payments'] ?? [];
        $rawPatientInput = trim((string) ($input['patient_id'] ?? ''));
        $patientId = null;
        $customerName = trim((string) ($input['customer_name'] ?? 'Walk-in Customer'));
        if ($rawPatientInput !== '') {
            $isUuid = strlen($rawPatientInput) === 36 && str_contains($rawPatientInput, '-');
            $pRow = $isUuid
                ? DB::selectOne(
                    "SELECT id, first_name, last_name FROM patients WHERE uuid = ? AND tenant_id = ? AND deleted_at IS NULL",
                    [$rawPatientInput, $tenantId]
                )
                : DB::selectOne(
                    "SELECT id, first_name, last_name FROM patients WHERE id = ? AND tenant_id = ? AND deleted_at IS NULL",
                    [(int)$rawPatientInput, $tenantId]
                );
            if ($pRow) {
                $patientId = (int) $pRow['id'];
                if ($customerName === 'Walk-in Customer' || empty($customerName)) {
                    $customerName = trim($pRow['first_name'] . ' ' . $pRow['last_name']);
                }
            }
        }
        $prescriptionRef = trim((string) ($input['prescription_ref'] ?? ''));
        $prescriberName = trim((string) ($input['prescriber_name'] ?? ''));
        $notes = trim((string) ($input['notes'] ?? ''));

        if (empty($items) || !is_array($items)) {
            throw new HttpException(422, 'Cart is empty');
        }

        // Active cash session check (or auto-create for user)
        $session = DB::selectOne(
            "SELECT * FROM cash_sessions WHERE tenant_id = ? AND user_id = ? AND status = 'open' LIMIT 1",
            [$tenantId, Auth::id()]
        );
        $sessionId = $session ? (int) $session['id'] : null;

        // Perform atomic checkout
        $sale = DB::transaction(function () use ($tenantId, $items, $payments, $patientId, $customerName, $prescriptionRef, $prescriberName, $notes, $sessionId) {
            $subtotalMinor = 0;
            $taxMinor = 0;
            $discountMinor = 0;
            $pomEncountered = false;

            // 1. Validate items and stock
            $resolvedItems = [];
            foreach ($items as $it) {
                $prodId = (int) ($it['product_id'] ?? 0);
                $qty = max(1, (int) ($it['qty'] ?? 1));

                $product = DB::selectOne('SELECT * FROM products WHERE id = ? AND tenant_id = ? AND is_active = 1', [$prodId, $tenantId]);
                if (!$product) {
                    throw new HttpException(422, "Product ID $prodId not found");
                }

                if ($product['stock_qty'] < $qty) {
                    throw new HttpException(422, "Insufficient stock for {$product['name']} (Requested: $qty, Available: {$product['stock_qty']})");
                }

                if (in_array($product['schedule'], ['pom', 'controlled'], true)) {
                    $pomEncountered = true;
                }

                $unitPrice = (int) $product['price_minor'];
                $itemDiscount = (int) ($it['discount_minor'] ?? 0);
                $lineSubtotal = ($unitPrice * $qty) - $itemDiscount;
                $taxRate = (float) ($product['tax_rate'] ?? 0);
                $lineTax = $taxRate > 0 ? (int) round($lineSubtotal * ($taxRate / 100)) : 0;

                $subtotalMinor += $lineSubtotal;
                $taxMinor += $lineTax;
                $discountMinor += $itemDiscount;

                $resolvedItems[] = [
                    'product' => $product,
                    'qty' => $qty,
                    'unit_price' => $unitPrice,
                    'discount_minor' => $itemDiscount,
                    'tax_minor' => $lineTax,
                    'total_minor' => $lineSubtotal + $lineTax,
                ];
            }

            if ($pomEncountered && empty($prescriptionRef)) {
                throw new HttpException(422, 'A prescription reference is legally required for POM / Controlled medicines.');
            }

            $grandTotalMinor = $subtotalMinor + $taxMinor;

            // 2. Validate payments
            $totalPaidMinor = 0;
            foreach ($payments as $p) {
                $totalPaidMinor += (int) ($p['amount_minor'] ?? 0);
            }

            if ($totalPaidMinor < $grandTotalMinor) {
                throw new HttpException(422, 'Paid amount is less than total amount due.');
            }

            // 3. Generate receipt number
            $receiptNo = Sequence::receipt();
            $now = now();

            // 4. Create Sale
            DB::statement(
                'INSERT INTO sales (tenant_id, branch_id, cash_session_id, receipt_no, patient_id, customer_name, prescription_ref, subtotal_minor, discount_minor, tax_minor, total_minor, paid_minor, change_minor, status, user_id, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $tenantId, 1, $sessionId, $receiptNo, $patientId,
                    $customerName, $prescriptionRef ?: null,
                    $subtotalMinor, $discountMinor, $taxMinor, $grandTotalMinor,
                    $totalPaidMinor, max(0, $totalPaidMinor - $grandTotalMinor),
                    'completed', Auth::id(), $now, $now,
                ]
            );

            $saleId = (int) DB::connection()->lastInsertId();

            // 5. FEFO Batch decrement & line items insertion
            foreach ($resolvedItems as $item) {
                $prod = $item['product'];
                $remainingQty = $item['qty'];

                // Grab batches sorted by earliest expiry date (FEFO) - only approved batches
                $batches = DB::select(
                    "SELECT * FROM product_batches WHERE product_id = ? AND tenant_id = ? AND qty_on_hand > 0 AND (status = 'approved' OR status IS NULL) ORDER BY expiry_date ASC, id ASC",
                    [$prod['id'], $tenantId]
                );

                foreach ($batches as $batch) {
                    if ($remainingQty <= 0) break;
                    $deduct = min($remainingQty, (int) $batch['qty_on_hand']);

                    DB::statement(
                        'UPDATE product_batches SET qty_on_hand = qty_on_hand - ?, updated_at = NOW() WHERE id = ?',
                        [$deduct, $batch['id']]
                    );

                    // Insert sale item
                    $linePortionSub = (int) round(($item['unit_price'] * $deduct) - ($item['discount_minor'] * ($deduct / $item['qty'])));
                    $taxRate = (float) ($prod['tax_rate'] ?? 0);
                    $linePortionTax = $taxRate > 0 ? (int) round($linePortionSub * ($taxRate / 100)) : 0;

                    DB::statement(
                        'INSERT INTO sale_items (tenant_id, sale_id, product_id, batch_id, name, qty, unit_price_minor, tax_rate, tax_minor, line_total_minor)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                        [
                            $tenantId, $saleId, $prod['id'], $batch['id'], $prod['name'], $deduct,
                            $item['unit_price'], $taxRate, $linePortionTax, $linePortionSub + $linePortionTax,
                        ]
                    );

                    // Stock movement ledger
                    DB::statement(
                        'INSERT INTO stock_movements (tenant_id, product_id, batch_id, type, qty, reference_type, reference_id, note, user_id, created_at)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                        [$tenantId, $prod['id'], $batch['id'], 'sale', -$deduct, 'sale', $saleId, "Sold via receipt $receiptNo", Auth::id(), $now]
                    );

                    $remainingQty -= $deduct;
                }

                // Decrement master product stock_qty
                DB::statement(
                    'UPDATE products SET stock_qty = stock_qty - ?, updated_at = NOW() WHERE id = ? AND tenant_id = ?',
                    [$item['qty'], $prod['id'], $tenantId]
                );
            }

            // 6. Record Payments
            foreach ($payments as $pay) {
                $payAmount = (int) ($pay['amount_minor'] ?? 0);
                if ($payAmount <= 0) continue;
                $method = $pay['method'] ?? 'cash';
                $ref = $pay['reference'] ?? null;

                DB::statement(
                    'INSERT INTO sale_payments (tenant_id, sale_id, method, amount_minor, reference, created_at) VALUES (?, ?, ?, ?, ?, ?)',
                    [$tenantId, $saleId, $method, $payAmount, $ref, $now]
                );
            }

            // 7. Update session if present
            if ($sessionId) {
                $cashPortion = 0;
                foreach ($payments as $pay) {
                    if (($pay['method'] ?? '') === 'cash') $cashPortion += (int) $pay['amount_minor'];
                }
                DB::statement(
                    'UPDATE cash_sessions SET total_sales_minor = total_sales_minor + ?, cash_sales_minor = cash_sales_minor + ?, updated_at = NOW() WHERE id = ?',
                    [$grandTotalMinor, $cashPortion, $sessionId]
                );
            }

            audit('sale.completed', 'Sale', $saleId, ['receipt_no' => $receiptNo, 'total_minor' => $grandTotalMinor]);

            return DB::selectOne('SELECT * FROM sales WHERE id = ?', [$saleId]);
        });

        return Response::json([
            'success' => true,
            'sale' => $sale,
            'sale_id' => $sale['uuid'] ?? $sale['id'],
            'receipt_no' => $sale['receipt_no'],
            'receipt_url' => route('pharmacy.receipt', ['id' => $sale['uuid'] ?? $sale['id']]),
            'message' => "Sale completed successfully. Receipt: {$sale['receipt_no']}",
        ]);
    }

    public function receipt(Request $request, int|string $id): Response
    {
        $tenantId = TenantManager::id();
        $sale = DB::selectOne(
            "SELECT s.*, u.name AS cashier_name, p.first_name, p.last_name, p.mrn, p.phone AS patient_phone
             FROM sales s
             LEFT JOIN users u ON u.id = s.user_id
             LEFT JOIN patients p ON p.id = s.patient_id
             WHERE (s.uuid = ? OR s.id = ?) AND s.tenant_id = ?",
            [$id, $id, $tenantId]
        );
        if (!$sale) {
            abort(404, 'Receipt not found');
        }

        $items = DB::select(
            "SELECT si.*, p.name AS product_name, p.generic_name, p.form, p.strength, b.batch_no, b.expiry_date
             FROM sale_items si
             JOIN products p ON p.id = si.product_id
             LEFT JOIN product_batches b ON b.id = si.batch_id
             WHERE si.sale_id = ? AND si.tenant_id = ?",
            [$sale['id'], $tenantId]
        );

        $payments = DB::select(
            'SELECT * FROM sale_payments WHERE sale_id = ? AND tenant_id = ?',
            [$sale['id'], $tenantId]
        );

        $tenant = TenantManager::current();

        return view('pharmacy.receipt', [
            'sale' => $sale,
            'items' => $items,
            'payments' => $payments,
            'tenant' => $tenant,
        ]);
    }
}
