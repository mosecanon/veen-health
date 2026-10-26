<?php
namespace App\Controllers;

use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\ProductCategory;
use App\Models\StockMovement;
use App\Models\Supplier;
use Veen\Core\Database\DB;
use Veen\Core\Request;
use Veen\Core\Response;
use Veen\Core\Tenancy\TenantManager;

class PharmacyController extends Controller
{
    public function index(Request $request): Response
    {
        $tenantId = TenantManager::id();
        $q = trim((string) $request->query('q', ''));
        $catId = (int) $request->query('category_id', 0);
        $schedule = trim((string) $request->query('schedule', ''));
        $tab = $request->query('tab', 'inventory'); // 'inventory', 'expiring', 'low_stock'

        $params = [$tenantId];
        $where = 'WHERE p.tenant_id = ?';

        if ($q !== '') {
            $where .= ' AND (p.name LIKE ? OR p.generic_name LIKE ? OR p.barcode LIKE ? OR p.sku LIKE ?)';
            $search = '%' . $q . '%';
            array_push($params, $search, $search, $search, $search);
        }
        if ($catId > 0) {
            $where .= ' AND p.category_id = ?';
            $params[] = $catId;
        }
        if ($schedule !== '') {
            $where .= ' AND p.schedule = ?';
            $params[] = $schedule;
        }

        if ($tab === 'low_stock') {
            $where .= ' AND p.stock_qty <= p.reorder_level';
        }

        $products = DB::select(
            "SELECT p.*, c.name AS category_name,
                    (SELECT COUNT(*) FROM product_batches b WHERE b.product_id = p.id AND b.qty_on_hand > 0) AS active_batches_count,
                    (SELECT MIN(b.expiry_date) FROM product_batches b WHERE b.product_id = p.id AND b.qty_on_hand > 0) AS earliest_expiry
             FROM products p
             LEFT JOIN product_categories c ON c.id = p.category_id
             $where
             ORDER BY p.name ASC LIMIT 100",
            $params
        );

        // Expiring batches in next 60 days
        $expiringBatches = DB::select(
            "SELECT b.*, p.name AS product_name, p.generic_name, p.form, p.strength, s.name AS supplier_name
             FROM product_batches b
             JOIN products p ON p.id = b.product_id
             LEFT JOIN suppliers s ON s.id = b.supplier_id
             WHERE b.tenant_id = ? AND b.qty_on_hand > 0 AND b.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 60 DAY)
             ORDER BY b.expiry_date ASC",
            [$tenantId]
        );

        $categories = DB::select('SELECT * FROM product_categories WHERE tenant_id = ? ORDER BY name ASC', [$tenantId]);
        $suppliers = DB::select('SELECT * FROM suppliers WHERE tenant_id = ? ORDER BY name ASC', [$tenantId]);

        // Inventory metrics: Units in stock, retail worth, wholesale valuation, and projected inventory margin
        $metrics = DB::selectOne(
            'SELECT COUNT(*) AS total_products,
                    COALESCE(SUM(stock_qty), 0) AS total_units,
                    COALESCE(SUM(stock_qty * price_minor), 0) AS retail_valuation_minor,
                    COALESCE(SUM(stock_qty * COALESCE(wholesale_price_minor, 0)), 0) AS wholesale_valuation_minor
             FROM products WHERE tenant_id = ? AND is_active = 1',
            [$tenantId]
        );
        $batchVal = DB::selectOne(
            'SELECT COALESCE(SUM(qty_on_hand * cost_minor), 0) AS batch_cost_valuation_minor
             FROM product_batches WHERE tenant_id = ? AND qty_on_hand > 0',
            [$tenantId]
        );
        $metrics['batch_cost_valuation_minor'] = (int) ($batchVal['batch_cost_valuation_minor'] ?? 0);
        if ((int) $metrics['wholesale_valuation_minor'] === 0 && $metrics['batch_cost_valuation_minor'] > 0) {
            $metrics['wholesale_valuation_minor'] = $metrics['batch_cost_valuation_minor'];
        }
        $metrics['projected_gross_margin_minor'] = max(0, (int) $metrics['retail_valuation_minor'] - (int) $metrics['wholesale_valuation_minor']);
        $metrics['projected_margin_pct'] = $metrics['retail_valuation_minor'] > 0
            ? round(($metrics['projected_gross_margin_minor'] / $metrics['retail_valuation_minor']) * 100, 1)
            : 0;

        return view('pharmacy.index', [
            'title' => 'Pharmacy & Inventory',
            'products' => $products,
            'expiringBatches' => $expiringBatches,
            'categories' => $categories,
            'suppliers' => $suppliers,
            'metrics' => $metrics,
            'q' => $q,
            'catId' => $catId,
            'schedule' => $schedule,
            'tab' => $tab,
        ]);
    }

    public function createProduct(Request $request): Response
    {
        $tenantId = TenantManager::id();
        $categories = DB::select('SELECT * FROM product_categories WHERE tenant_id = ? ORDER BY name ASC', [$tenantId]);
        return view('pharmacy.products.create', [
            'title' => 'Add Pharmacy Medicine / Item',
            'categories' => $categories,
        ]);
    }

    public function storeProduct(Request $request): Response
    {
        $tenantId = TenantManager::id();
        $data = $this->validate($request, [
            'name'            => 'required|string|max:120',
            'generic_name'    => 'nullable|string|max:120',
            'category_id'     => 'nullable|integer',
            'barcode'         => 'nullable|string|max:64',
            'sku'             => 'nullable|string|max:64',
            'form'            => 'required|string|max:40',
            'strength'        => 'nullable|string|max:40',
            'unit'            => 'required|string|max:30',
            'schedule'        => 'required|in:otc,pom,controlled',
            'price'           => 'required|numeric|min:0',
            'wholesale_price' => 'nullable|numeric|min:0',
            'tax_rate'        => 'nullable|numeric|min:0',
            'reorder_level'   => 'nullable|integer|min:0',
        ]);

        $sku = $data['sku'] ?: ('MED-' . strtoupper(bin2hex(random_bytes(3))));
        $barcode = !empty($data['barcode']) ? trim($data['barcode']) : ('MED' . mt_rand(10000000, 99999999));
        $priceMinor = to_minor($data['price']);
        $wholesalePriceMinor = !empty($data['wholesale_price']) ? to_minor($data['wholesale_price']) : 0;

        $product = Product::create([
            'category_id'           => $data['category_id'] ?: null,
            'name'                  => $data['name'],
            'generic_name'          => $data['generic_name'] ?: null,
            'barcode'               => $barcode,
            'sku'                   => $sku,
            'form'                  => $data['form'],
            'strength'              => $data['strength'] ?: null,
            'unit'                  => $data['unit'],
            'schedule'              => $data['schedule'],
            'price_minor'           => $priceMinor,
            'wholesale_price_minor' => $wholesalePriceMinor,
            'tax_rate'              => $data['tax_rate'] ?? 0,
            'reorder_level'         => $data['reorder_level'] ?? 10,
            'stock_qty'             => 0,
            'is_active'             => 1,
        ]);

        audit('product.created', 'Product', $product->id, ['name' => $product->name, 'sku' => $sku]);

        return redirect(route('pharmacy.index'))->with('success', "Item {$product->name} added successfully.");
    }

    public function receiveBatchForm(Request $request): Response
    {
        $tenantId = TenantManager::id();
        $products = DB::select('SELECT id, name, generic_name, form, strength, price_minor FROM products WHERE tenant_id = ? AND is_active = 1 ORDER BY name ASC', [$tenantId]);
        $suppliers = DB::select('SELECT * FROM suppliers WHERE tenant_id = ? ORDER BY name ASC', [$tenantId]);

        return view('pharmacy.batches.receive', [
            'title' => 'Receive Stock / New Batch',
            'products' => $products,
            'suppliers' => $suppliers,
            'productId' => (int) $request->query('product_id', 0),
        ]);
    }

    public function receiveBatch(Request $request): Response
    {
        $tenantId = TenantManager::id();
        $data = $this->validate($request, [
            'product_id'        => 'required|integer',
            'supplier_id'       => 'nullable|integer',
            'batch_no'          => 'required|string|max:60',
            'delivery_note_no'  => 'nullable|string|max:60',
            'expiry_date'       => 'required|date',
            'cost'              => 'required|numeric|min:0',
            'qty_received'      => 'required|integer|min:1',
            'note'              => 'nullable|string|max:255',
            'require_approval'  => 'nullable',
        ]);

        $product = Product::find((int) $data['product_id']);
        if (!$product) abort(404, 'Product not found');

        $qty = (int) $data['qty_received'];
        $costMinor = to_minor($data['cost']);
        $deliveryNote = trim((string) ($data['delivery_note_no'] ?? ''));
        $requireApproval = !empty($data['require_approval']);

        if ($requireApproval) {
            // Stock is pending approval: NOT yet added to inventory and can be edited
            $batch = ProductBatch::create([
                'product_id'       => $product->id,
                'supplier_id'      => $data['supplier_id'] ?: null,
                'batch_no'         => $data['batch_no'],
                'delivery_note_no' => $deliveryNote ?: null,
                'expiry_date'      => $data['expiry_date'],
                'cost_minor'       => $costMinor,
                'qty_received'     => $qty,
                'qty_on_hand'      => 0,
                'status'           => 'pending_approval',
                'received_at'      => now(),
                'received_by'      => \Veen\Core\Auth::id(),
            ]);

            audit('stock.restock_pending', 'ProductBatch', $batch->id, ['product' => $product->name, 'batch' => $data['batch_no'], 'qty' => $qty]);

            return redirect(route('pharmacy.batches.index'))->with('success', "Restock batch {$data['batch_no']} submitted for supervisor approval (Qty: $qty units).");
        }

        // Direct immediate approval
        DB::transaction(function () use ($tenantId, $product, $data, $qty, $costMinor, $deliveryNote) {
            $batch = ProductBatch::create([
                'product_id'       => $product->id,
                'supplier_id'      => $data['supplier_id'] ?: null,
                'batch_no'         => $data['batch_no'],
                'delivery_note_no' => $deliveryNote ?: null,
                'expiry_date'      => $data['expiry_date'],
                'cost_minor'       => $costMinor,
                'qty_received'     => $qty,
                'qty_on_hand'      => $qty,
                'status'           => 'approved',
                'received_at'      => now(),
                'received_by'      => \Veen\Core\Auth::id(),
                'approved_by'      => \Veen\Core\Auth::id(),
                'approved_at'      => now(),
            ]);

            StockMovement::create([
                'product_id'     => $product->id,
                'batch_id'       => $batch->id,
                'type'           => 'receive',
                'qty'            => $qty,
                'reference_type' => 'batch',
                'reference_id'   => $batch->id,
                'note'           => $data['note'] ?: 'Received batch ' . $data['batch_no'],
                'created_at'     => now(),
            ]);

            DB::statement(
                'UPDATE products SET stock_qty = stock_qty + ?, updated_at = NOW() WHERE id = ? AND tenant_id = ?',
                [$qty, $product->id, $tenantId]
            );
        });

        audit('stock.received', 'ProductBatch', null, ['product' => $product->name, 'batch' => $data['batch_no'], 'qty' => $qty]);

        return redirect(route('pharmacy.index'))->with('success', "Received and approved $qty units of {$product->name} (Batch {$data['batch_no']}).");
    }

    public function batchesIndex(Request $request): Response
    {
        $tenantId = TenantManager::id();
        $status = $request->query('status', 'all');
        $search = trim((string) $request->query('q', ''));

        $where = "WHERE b.tenant_id = ?";
        $params = [$tenantId];

        if ($status !== 'all' && in_array($status, ['pending_approval', 'approved', 'rejected'], true)) {
            $where .= " AND b.status = ?";
            $params[] = $status;
        }

        if ($search !== '') {
            $where .= " AND (b.batch_no LIKE ? OR b.delivery_note_no LIKE ? OR p.name LIKE ? OR s.name LIKE ?)";
            $searchTerm = '%' . $search . '%';
            array_push($params, $searchTerm, $searchTerm, $searchTerm, $searchTerm);
        }

        $batches = DB::select(
            "SELECT b.*, p.name AS product_name, p.generic_name, p.sku, p.form, p.strength,
                    s.name AS supplier_name,
                    u_rec.name AS received_by_name,
                    u_app.name AS approved_by_name
             FROM product_batches b
             JOIN products p ON p.id = b.product_id
             LEFT JOIN suppliers s ON s.id = b.supplier_id
             LEFT JOIN users u_rec ON u_rec.id = b.received_by
             LEFT JOIN users u_app ON u_app.id = b.approved_by
             $where
             ORDER BY b.id DESC LIMIT 200",
            $params
        );

        $counts = [
            'all' => (int) (DB::selectOne("SELECT COUNT(*) AS c FROM product_batches WHERE tenant_id = ?", [$tenantId])['c'] ?? 0),
            'pending' => (int) (DB::selectOne("SELECT COUNT(*) AS c FROM product_batches WHERE tenant_id = ? AND status = 'pending_approval'", [$tenantId])['c'] ?? 0),
            'approved' => (int) (DB::selectOne("SELECT COUNT(*) AS c FROM product_batches WHERE tenant_id = ? AND status = 'approved'", [$tenantId])['c'] ?? 0),
            'rejected' => (int) (DB::selectOne("SELECT COUNT(*) AS c FROM product_batches WHERE tenant_id = ? AND status = 'rejected'", [$tenantId])['c'] ?? 0),
        ];

        return view('pharmacy.batches.index', [
            'title' => 'Stock Restocks & Batch Approvals',
            'batches' => $batches,
            'status' => $status,
            'search' => $search,
            'counts' => $counts,
        ]);
    }

    public function editBatch(Request $request, int $id): Response
    {
        $tenantId = TenantManager::id();
        $batch = DB::selectOne(
            "SELECT b.*, p.name AS product_name, p.generic_name, p.form, p.strength
             FROM product_batches b
             JOIN products p ON p.id = b.product_id
             WHERE b.id = ? AND b.tenant_id = ?",
            [$id, $tenantId]
        );

        if (!$batch) {
            abort(404, 'Batch not found');
        }

        if ($batch['status'] === 'approved') {
            return redirect(route('pharmacy.batches.index'))->with('error', 'Approved restocks cannot be modified to preserve inventory audit integrity.');
        }

        if ($batch['status'] === 'rejected') {
            return redirect(route('pharmacy.batches.index'))->with('error', 'Rejected restocks cannot be modified.');
        }

        $suppliers = DB::select('SELECT * FROM suppliers WHERE tenant_id = ? ORDER BY name ASC', [$tenantId]);

        return view('pharmacy.batches.edit', [
            'title' => "Edit Pending Restock: Batch {$batch['batch_no']}",
            'batch' => $batch,
            'suppliers' => $suppliers,
        ]);
    }

    public function updateBatch(Request $request, int $id): Response
    {
        $tenantId = TenantManager::id();
        $batch = DB::selectOne("SELECT * FROM product_batches WHERE id = ? AND tenant_id = ?", [$id, $tenantId]);
        if (!$batch) {
            abort(404, 'Batch not found');
        }

        if ($batch['status'] === 'approved') {
            return redirect(route('pharmacy.batches.index'))->with('error', 'Approved restocks cannot be edited.');
        }

        $data = $this->validate($request, [
            'batch_no'         => 'required|string|max:60',
            'delivery_note_no' => 'nullable|string|max:60',
            'expiry_date'      => 'required|date',
            'cost'             => 'required|numeric|min:0',
            'qty_received'     => 'required|integer|min:1',
            'supplier_id'      => 'nullable|integer',
        ]);

        $costMinor = to_minor($data['cost']);
        $qty = (int) $data['qty_received'];

        DB::statement(
            "UPDATE product_batches
             SET batch_no = ?, delivery_note_no = ?, expiry_date = ?, cost_minor = ?, qty_received = ?, supplier_id = ?, updated_at = NOW()
             WHERE id = ? AND tenant_id = ?",
            [
                $data['batch_no'],
                $data['delivery_note_no'] ?: null,
                $data['expiry_date'],
                $costMinor,
                $qty,
                $data['supplier_id'] ?: null,
                $id,
                $tenantId,
            ]
        );

        audit('stock.restock_updated', 'ProductBatch', $id, ['batch' => $data['batch_no'], 'qty' => $qty]);

        return redirect(route('pharmacy.batches.index'))->with('success', "Batch {$data['batch_no']} updated successfully.");
    }

    public function approveBatch(Request $request, int $id): Response
    {
        $tenantId = TenantManager::id();
        $batch = DB::selectOne(
            "SELECT b.*, p.name AS product_name
             FROM product_batches b
             JOIN products p ON p.id = b.product_id
             WHERE b.id = ? AND b.tenant_id = ?",
            [$id, $tenantId]
        );

        if (!$batch) {
            abort(404, 'Batch not found');
        }

        if ($batch['status'] !== 'pending_approval') {
            return back()->with('error', "Batch {$batch['batch_no']} is already {$batch['status']} and cannot be approved again.");
        }

        $qty = (int) $batch['qty_received'];
        $now = now();

        DB::transaction(function () use ($tenantId, $batch, $qty, $now) {
            DB::statement(
                "UPDATE product_batches
                 SET status = 'approved', qty_on_hand = ?, approved_by = ?, approved_at = ?, updated_at = NOW()
                 WHERE id = ? AND tenant_id = ?",
                [$qty, \Veen\Core\Auth::id(), $now, $batch['id'], $tenantId]
            );

            DB::statement(
                "UPDATE products SET stock_qty = stock_qty + ?, updated_at = NOW() WHERE id = ? AND tenant_id = ?",
                [$qty, $batch['product_id'], $tenantId]
            );

            DB::statement(
                "INSERT INTO stock_movements (tenant_id, product_id, batch_id, type, qty, reference_type, reference_id, note, user_id, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $tenantId, $batch['product_id'], $batch['id'], 'receive', $qty, 'batch', $batch['id'],
                    "Approved restock batch {$batch['batch_no']}", \Veen\Core\Auth::id(), $now
                ]
            );
        });

        audit('stock.approved', 'ProductBatch', $batch['id'], ['product' => $batch['product_name'], 'batch' => $batch['batch_no'], 'qty' => $qty]);

        return redirect(route('pharmacy.batches.index'))->with('success', "Batch {$batch['batch_no']} approved! $qty units added to active inventory for {$batch['product_name']}.");
    }

    public function rejectBatch(Request $request, int $id): Response
    {
        $tenantId = TenantManager::id();
        $batch = DB::selectOne("SELECT * FROM product_batches WHERE id = ? AND tenant_id = ?", [$id, $tenantId]);
        if (!$batch) {
            abort(404, 'Batch not found');
        }

        if ($batch['status'] !== 'pending_approval') {
            return back()->with('error', "Only batches pending approval can be rejected.");
        }

        $reason = trim((string) $request->input('rejection_reason', 'Rejected by supervisor'));
        $now = now();

        DB::statement(
            "UPDATE product_batches
             SET status = 'rejected', rejection_reason = ?, approved_by = ?, approved_at = ?, updated_at = NOW()
             WHERE id = ? AND tenant_id = ?",
            [$reason, \Veen\Core\Auth::id(), $now, $id, $tenantId]
        );

        audit('stock.rejected', 'ProductBatch', $id, ['batch' => $batch['batch_no'], 'reason' => $reason]);

        return redirect(route('pharmacy.batches.index'))->with('success', "Batch {$batch['batch_no']} was rejected.");
    }

    public function salesReport(Request $request): Response
    {
        $tenantId = TenantManager::id();
        $date = $request->query('date', date('Y-m-d'));
        $paymentFilter = $request->query('payment_method', '');

        // Daily totals broken down by payment methods
        $methodTotals = DB::select(
            "SELECT sp.method, COALESCE(SUM(sp.amount_minor), 0) AS total_amount, COUNT(DISTINCT s.id) AS txn_count
             FROM sale_payments sp
             JOIN sales s ON s.id = sp.sale_id
             WHERE s.tenant_id = ? AND s.status = 'completed' AND DATE(s.created_at) = ?
             GROUP BY sp.method",
            [$tenantId, $date]
        );

        $methodBreakdown = [
            'cash' => 0,
            'mpesa' => 0,
            'card' => 0,
            'bank_transfer' => 0,
            'other' => 0,
        ];
        $totalSalesMinor = 0;
        foreach ($methodTotals as $mt) {
            $m = strtolower($mt['method']);
            if (isset($methodBreakdown[$m])) {
                $methodBreakdown[$m] = (int) $mt['total_amount'];
            } else {
                $methodBreakdown['other'] += (int) $mt['total_amount'];
            }
            $totalSalesMinor += (int) $mt['total_amount'];
        }

        // Summary counts
        $daySummary = DB::selectOne(
            "SELECT COUNT(DISTINCT s.id) AS total_transactions,
                    COALESCE(SUM(si.qty), 0) AS total_units_sold,
                    COALESCE(SUM(s.discount_minor), 0) AS total_discount,
                    COALESCE(SUM(s.tax_minor), 0) AS total_tax
             FROM sales s
             LEFT JOIN sale_items si ON si.sale_id = s.id
             WHERE s.tenant_id = ? AND s.status = 'completed' AND DATE(s.created_at) = ?",
            [$tenantId, $date]
        );

        // Sales query
        $where = "WHERE s.tenant_id = ? AND DATE(s.created_at) = ?";
        $params = [$tenantId, $date];

        if ($paymentFilter !== '') {
            $where .= " AND EXISTS (SELECT 1 FROM sale_payments sp2 WHERE sp2.sale_id = s.id AND sp2.method = ?)";
            $params[] = $paymentFilter;
        }

        $sales = DB::select(
            "SELECT s.*, u.name AS cashier_name, p.mrn, p.dob,
                    (SELECT GROUP_CONCAT(CONCAT(si.qty, 'x ', si.name) SEPARATOR ', ') FROM sale_items si WHERE si.sale_id = s.id) AS items_summary,
                    (SELECT GROUP_CONCAT(CONCAT(UPPER(sp.method), ': ', sp.amount_minor) SEPARATOR ' | ') FROM sale_payments sp WHERE sp.sale_id = s.id) AS payments_summary,
                    (SELECT GROUP_CONCAT(sp.method SEPARATOR ',') FROM sale_payments sp WHERE sp.sale_id = s.id) AS payment_methods_list
             FROM sales s
             LEFT JOIN users u ON u.id = s.user_id
             LEFT JOIN patients p ON p.id = s.patient_id
             $where
             ORDER BY s.id DESC LIMIT 150",
            $params
        );

        return view('pharmacy.sales', [
            'title' => "Today's Sales & Revenue Report",
            'date' => $date,
            'paymentFilter' => $paymentFilter,
            'sales' => $sales,
            'methodBreakdown' => $methodBreakdown,
            'totalSalesMinor' => $totalSalesMinor,
            'daySummary' => $daySummary,
        ]);
    }

    public function stockMovements(Request $request): Response
    {
        $tenantId = TenantManager::id();
        $type = $request->query('type', '');
        $productId = (int) $request->query('product_id', 0);
        $dateFrom = $request->query('date_from', date('Y-m-d', strtotime('-30 days')));
        $dateTo = $request->query('date_to', date('Y-m-d'));

        $where = "WHERE sm.tenant_id = ? AND DATE(sm.created_at) BETWEEN ? AND ?";
        $params = [$tenantId, $dateFrom, $dateTo];

        if ($type !== '') {
            $where .= " AND sm.type = ?";
            $params[] = $type;
        }
        if ($productId > 0) {
            $where .= " AND sm.product_id = ?";
            $params[] = $productId;
        }

        $movements = DB::select(
            "SELECT sm.*, p.name AS product_name, p.generic_name, p.sku, p.form,
                    b.batch_no, b.expiry_date, u.name AS user_name
             FROM stock_movements sm
             JOIN products p ON p.id = sm.product_id
             LEFT JOIN product_batches b ON b.id = sm.batch_id
             LEFT JOIN users u ON u.id = sm.user_id
             $where
             ORDER BY sm.id DESC LIMIT 200",
            $params
        );

        $products = DB::select('SELECT id, name, sku FROM products WHERE tenant_id = ? ORDER BY name ASC', [$tenantId]);

        return view('pharmacy.stock_movements', [
            'title' => 'Detailed Inventory Stock Ledger',
            'movements' => $movements,
            'products' => $products,
            'type' => $type,
            'productId' => $productId,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ]);
    }

    public function suppliers(Request $request): Response
    {
        $tenantId = TenantManager::id();
        $search = trim((string) $request->query('q', ''));

        $where = "WHERE s.tenant_id = ?";
        $params = [$tenantId];

        if ($search !== '') {
            $where .= " AND (s.name LIKE ? OR s.contact_person LIKE ? OR s.phone LIKE ? OR s.email LIKE ?)";
            $searchTerm = '%' . $search . '%';
            array_push($params, $searchTerm, $searchTerm, $searchTerm, $searchTerm);
        }

        $suppliers = DB::select(
            "SELECT s.*,
                    COUNT(DISTINCT b.id) AS total_batches_all_time,
                    COALESCE(SUM(b.qty_received), 0) AS total_units_all_time,
                    COALESCE(SUM(b.cost_minor * b.qty_received), 0) AS total_value_all_time,
                    COUNT(DISTINCT CASE WHEN YEAR(b.received_at) = YEAR(CURDATE()) THEN b.id END) AS total_batches_this_year,
                    COALESCE(SUM(CASE WHEN YEAR(b.received_at) = YEAR(CURDATE()) THEN b.qty_received ELSE 0 END), 0) AS total_units_this_year,
                    COALESCE(SUM(CASE WHEN YEAR(b.received_at) = YEAR(CURDATE()) THEN b.cost_minor * b.qty_received ELSE 0 END), 0) AS total_value_this_year,
                    MAX(b.received_at) AS last_supply_date
             FROM suppliers s
             LEFT JOIN product_batches b ON b.supplier_id = s.id AND b.tenant_id = s.tenant_id
             $where
             GROUP BY s.id
             ORDER BY s.name ASC",
            $params
        );

        $summary = DB::selectOne(
            "SELECT COUNT(DISTINCT s.id) AS total_suppliers,
                    COUNT(DISTINCT b.id) AS total_batches_received,
                    COALESCE(SUM(CASE WHEN YEAR(b.received_at) = YEAR(CURDATE()) THEN b.cost_minor * b.qty_received ELSE 0 END), 0) AS total_spend_this_year,
                    COALESCE(SUM(CASE WHEN YEAR(b.received_at) = YEAR(CURDATE()) THEN b.qty_received ELSE 0 END), 0) AS total_units_this_year
             FROM suppliers s
             LEFT JOIN product_batches b ON b.supplier_id = s.id AND b.tenant_id = s.tenant_id
             WHERE s.tenant_id = ?",
            [$tenantId]
        );

        return view('pharmacy.suppliers', [
            'title' => 'Pharmaceutical Suppliers & Supply Chain Analytics',
            'suppliers' => $suppliers,
            'summary' => $summary,
            'search' => $search,
        ]);
    }

    public function storeSupplier(Request $request): Response
    {
        $tenantId = TenantManager::id();
        $data = $this->validate($request, [
            'name'           => 'required|string|max:150',
            'contact_person' => 'nullable|string|max:100',
            'phone'          => 'nullable|string|max:30',
            'email'          => 'nullable|email|max:150',
            'address'        => 'nullable|string|max:255',
        ]);

        $id = DB::insert(
            "INSERT INTO suppliers (tenant_id, name, contact_person, phone, email, address, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())",
            [
                $tenantId,
                $data['name'],
                $data['contact_person'] ?: null,
                $data['phone'] ?: null,
                $data['email'] ?: null,
                $data['address'] ?: null,
            ]
        );

        audit('supplier.created', 'Supplier', $id, ['name' => $data['name']]);

        return redirect(route('pharmacy.suppliers.index'))->with('success', "Supplier '{$data['name']}' added successfully.");
    }

    public function quickStoreSupplier(Request $request): Response
    {
        $tenantId = TenantManager::id();
        $name = trim((string) $request->input('name'));
        $contactPerson = trim((string) $request->input('contact_person', ''));
        $phone = trim((string) $request->input('phone', ''));
        $email = trim((string) $request->input('email', ''));

        if (empty($name)) {
            return Response::json(['success' => false, 'message' => 'Supplier name is required.'], 422);
        }

        $id = DB::insert(
            "INSERT INTO suppliers (tenant_id, name, contact_person, phone, email, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, NOW(), NOW())",
            [
                $tenantId,
                $name,
                $contactPerson ?: null,
                $phone ?: null,
                $email ?: null,
            ]
        );

        audit('supplier.quick_created', 'Supplier', $id, ['name' => $name]);

        return Response::json([
            'success' => true,
            'message' => 'Supplier registered successfully',
            'supplier' => [
                'id' => $id,
                'name' => $name,
            ],
        ]);
    }

    public function updateSupplier(Request $request, int $id): Response
    {
        $tenantId = TenantManager::id();
        $supplier = DB::selectOne("SELECT * FROM suppliers WHERE id = ? AND tenant_id = ?", [$id, $tenantId]);
        if (!$supplier) {
            abort(404, 'Supplier not found');
        }

        $data = $this->validate($request, [
            'name'           => 'required|string|max:150',
            'contact_person' => 'nullable|string|max:100',
            'phone'          => 'nullable|string|max:30',
            'email'          => 'nullable|email|max:150',
            'address'        => 'nullable|string|max:255',
        ]);

        DB::statement(
            "UPDATE suppliers
             SET name = ?, contact_person = ?, phone = ?, email = ?, address = ?, updated_at = NOW()
             WHERE id = ? AND tenant_id = ?",
            [
                $data['name'],
                $data['contact_person'] ?: null,
                $data['phone'] ?: null,
                $data['email'] ?: null,
                $data['address'] ?: null,
                $id,
                $tenantId,
            ]
        );

        audit('supplier.updated', 'Supplier', $id, ['name' => $data['name']]);

        return redirect(route('pharmacy.suppliers.index'))->with('success', "Supplier '{$data['name']}' updated successfully.");
    }

    public function deleteSupplier(Request $request, int $id): Response
    {
        $tenantId = TenantManager::id();
        $supplier = DB::selectOne("SELECT * FROM suppliers WHERE id = ? AND tenant_id = ?", [$id, $tenantId]);
        if (!$supplier) {
            abort(404, 'Supplier not found');
        }

        $batchCount = (int) (DB::selectOne("SELECT COUNT(*) AS c FROM product_batches WHERE supplier_id = ? AND tenant_id = ?", [$id, $tenantId])['c'] ?? 0);
        if ($batchCount > 0) {
            return back()->with('error', "Cannot delete supplier '{$supplier['name']}' because $batchCount stock batch(es) are linked to this supplier.");
        }

        DB::statement("DELETE FROM suppliers WHERE id = ? AND tenant_id = ?", [$id, $tenantId]);

        audit('supplier.deleted', 'Supplier', $id, ['name' => $supplier['name']]);

        return redirect(route('pharmacy.suppliers.index'))->with('success', "Supplier '{$supplier['name']}' deleted successfully.");
    }

    public function categories(Request $request): Response
    {
        $tenantId = TenantManager::id();
        $categories = DB::select(
            "SELECT c.*, COUNT(p.id) AS products_count,
                    COALESCE(SUM(p.stock_qty), 0) AS total_stock_units,
                    COALESCE(SUM(p.stock_qty * COALESCE(p.wholesale_price_minor, 0)), 0) AS category_wholesale_minor,
                    COALESCE(SUM(p.stock_qty * p.price_minor), 0) AS category_retail_minor
             FROM product_categories c
             LEFT JOIN products p ON p.category_id = c.id AND p.is_active = 1
             WHERE c.tenant_id = ?
             GROUP BY c.id
             ORDER BY c.name ASC",
            [$tenantId]
        );

        return view('pharmacy.categories.index', [
            'title' => 'Pharmacy Product Categories',
            'categories' => $categories,
        ]);
    }

    public function storeCategory(Request $request): Response
    {
        $tenantId = TenantManager::id();
        $name = trim((string) $request->input('name', ''));
        if ($name === '') {
            return back()->with('error', 'Category name is required.');
        }

        $existing = DB::selectOne(
            "SELECT id FROM product_categories WHERE tenant_id = ? AND LOWER(name) = LOWER(?)",
            [$tenantId, $name]
        );
        if ($existing) {
            return back()->with('error', "A category named '{$name}' already exists.");
        }

        $uuid = generate_uuid();
        DB::statement(
            "INSERT INTO product_categories (uuid, tenant_id, name, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())",
            [$uuid, $tenantId, $name]
        );
        $id = DB::getPdo()->lastInsertId();

        audit('product_category.created', 'ProductCategory', (int)$id, ['name' => $name]);

        return back()->with('success', "Category '{$name}' created successfully.");
    }

    public function quickStoreCategory(Request $request): Response
    {
        $tenantId = TenantManager::id();
        $name = trim((string) $request->input('name', ''));
        if ($name === '') {
            return Response::json(['success' => false, 'message' => 'Category name is required.'], 422);
        }

        $existing = DB::selectOne(
            "SELECT id, uuid, name FROM product_categories WHERE tenant_id = ? AND LOWER(name) = LOWER(?)",
            [$tenantId, $name]
        );
        if ($existing) {
            return Response::json([
                'success' => true,
                'category' => $existing,
                'message' => "Category '{$name}' already exists and has been selected."
            ]);
        }

        $uuid = generate_uuid();
        DB::statement(
            "INSERT INTO product_categories (uuid, tenant_id, name, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())",
            [$uuid, $tenantId, $name]
        );
        $id = DB::getPdo()->lastInsertId();

        audit('product_category.quick_created', 'ProductCategory', (int)$id, ['name' => $name]);

        return Response::json([
            'success' => true,
            'category' => [
                'id' => (int) $id,
                'uuid' => $uuid,
                'name' => $name,
            ],
            'message' => "Category '{$name}' created successfully."
        ]);
    }

    public function deleteCategory(Request $request, int|string $id): Response
    {
        $tenantId = TenantManager::id();
        $isUuid = is_string($id) && strlen($id) === 36 && str_contains($id, '-');
        $category = $isUuid
            ? DB::selectOne("SELECT * FROM product_categories WHERE uuid = ? AND tenant_id = ?", [$id, $tenantId])
            : DB::selectOne("SELECT * FROM product_categories WHERE id = ? AND tenant_id = ?", [(int)$id, $tenantId]);
        if (!$category) {
            abort(404, 'Category not found');
        }

        // Unbind any linked products to prevent orphan foreign key constraints
        DB::statement(
            "UPDATE products SET category_id = NULL WHERE category_id = ? AND tenant_id = ?",
            [$category['id'], $tenantId]
        );

        DB::statement(
            "DELETE FROM product_categories WHERE id = ? AND tenant_id = ?",
            [$category['id'], $tenantId]
        );

        audit('product_category.deleted', 'ProductCategory', $category['id'], ['name' => $category['name']]);

        return back()->with('success', "Category '{$category['name']}' deleted successfully.");
    }
}
