<?php
namespace App\Controllers;

use App\Models\User;
use Veen\Core\Auth;
use Veen\Core\Database\DB;
use Veen\Core\Request;
use Veen\Core\Response;
use Veen\Core\Tenancy\TenantManager;

class HrController extends Controller
{
    public function index(Request $request): Response
    {
        $tenantId = TenantManager::id();
        $currentMonth = date('Y-m');

        // Staff counts
        $totalStaff = (int) (DB::selectOne("SELECT COUNT(*) AS c FROM users WHERE tenant_id = ? AND is_active = 1", [$tenantId])['c'] ?? 0);

        // Staff currently on leave today
        $onLeaveToday = (int) (DB::selectOne(
            "SELECT COUNT(*) AS c FROM hr_leaves WHERE tenant_id = ? AND status = 'approved' AND CURDATE() BETWEEN start_date AND end_date",
            [$tenantId]
        )['c'] ?? 0);

        // Pending leave applications
        $pendingLeaves = (int) (DB::selectOne(
            "SELECT COUNT(*) AS c FROM hr_leaves WHERE tenant_id = ? AND status = 'pending'",
            [$tenantId]
        )['c'] ?? 0);

        // Monthly payroll total
        $payrollSummary = DB::selectOne(
            "SELECT COALESCE(SUM(net_salary_minor), 0) AS total_net,
                    COUNT(*) AS payroll_count
             FROM hr_salaries
             WHERE tenant_id = ? AND month_year = ?",
            [$tenantId, $currentMonth]
        );

        // This month's operating expenses
        $expensesSummary = DB::selectOne(
            "SELECT COALESCE(SUM(amount_minor), 0) AS total_expenses,
                    COUNT(*) AS expense_count
             FROM hospital_expenses
             WHERE tenant_id = ? AND expense_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01')",
            [$tenantId]
        );

        // Recent leave requests
        $recentLeaves = DB::select(
            "SELECT l.*, u.name AS employee_name, u.title AS employee_title, u.role AS employee_role
             FROM hr_leaves l
             JOIN users u ON u.id = l.user_id
             WHERE l.tenant_id = ?
             ORDER BY l.id DESC LIMIT 6",
            [$tenantId]
        );

        // Recent expenses
        $recentExpenses = DB::select(
            "SELECT e.*, u.name AS recorded_by_name
             FROM hospital_expenses e
             LEFT JOIN users u ON u.id = e.recorded_by_user_id
             WHERE e.tenant_id = ?
             ORDER BY e.expense_date DESC, e.id DESC LIMIT 6",
            [$tenantId]
        );

        return view('hr.index', [
            'title' => 'Human Resources & Operating Expenses',
            'totalStaff' => $totalStaff,
            'onLeaveToday' => $onLeaveToday,
            'pendingLeaves' => $pendingLeaves,
            'payrollSummary' => $payrollSummary,
            'expensesSummary' => $expensesSummary,
            'recentLeaves' => $recentLeaves,
            'recentExpenses' => $recentExpenses,
            'currentMonth' => $currentMonth,
        ]);
    }

    public function leaves(Request $request): Response
    {
        $tenantId = TenantManager::id();
        $statusFilter = $request->query('status', '');

        $where = "WHERE l.tenant_id = ?";
        $params = [$tenantId];

        if ($statusFilter !== '') {
            $where .= " AND l.status = ?";
            $params[] = $statusFilter;
        }

        $leaves = DB::select(
            "SELECT l.*, u.name AS employee_name, u.title AS employee_title, u.role AS employee_role,
                    appr.name AS approved_by_name
             FROM hr_leaves l
             JOIN users u ON u.id = l.user_id
             LEFT JOIN users appr ON appr.id = l.approved_by
             $where
             ORDER BY CASE l.status WHEN 'pending' THEN 1 ELSE 2 END, l.start_date DESC",
            $params
        );

        $staff = DB::select("SELECT id, name, title, role FROM users WHERE tenant_id = ? AND is_active = 1 ORDER BY name ASC", [$tenantId]);

        return view('hr.leaves', [
            'title' => 'Staff Leave Management',
            'leaves' => $leaves,
            'staff' => $staff,
            'statusFilter' => $statusFilter,
        ]);
    }

    public function storeLeave(Request $request): Response
    {
        $tenantId = TenantManager::id();

        $data = $this->validate($request, [
            'user_id'    => 'required|integer',
            'leave_type' => 'required|string|in:annual,sick,maternity,paternity,compassionate,study,unpaid',
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
            'reason'     => 'nullable|string|max:500',
        ]);

        $userId = (int) $data['user_id'];
        $user = DB::selectOne("SELECT name FROM users WHERE id = ? AND tenant_id = ?", [$userId, $tenantId]);
        if (!$user) abort(404, 'Employee not found');

        $start = new \DateTime($data['start_date']);
        $end = new \DateTime($data['end_date']);
        $daysCount = max(1, $start->diff($end)->days + 1);

        DB::statement(
            "INSERT INTO hr_leaves (tenant_id, user_id, leave_type, start_date, end_date, days_count, reason, status, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW(), NOW())",
            [
                $tenantId,
                $userId,
                $data['leave_type'],
                $data['start_date'],
                $data['end_date'],
                $daysCount,
                trim($data['reason'] ?? ''),
            ]
        );

        audit('hr.leave_requested', 'HrLeave', null, ['employee' => $user['name'], 'days' => $daysCount, 'type' => $data['leave_type']]);

        return redirect(route('hr.leaves'))->with('success', "Leave request of {$daysCount} days recorded for {$user['name']}.");
    }

    public function updateLeaveStatus(Request $request, int|string $id): Response
    {
        $tenantId = TenantManager::id();
        $leave = DB::selectOne("SELECT l.*, u.name AS employee_name FROM hr_leaves l JOIN users u ON u.id = l.user_id WHERE l.id = ? AND l.tenant_id = ?", [$id, $tenantId]);
        if (!$leave) abort(404);

        $action = $request->input('action'); // 'approve' or 'reject'
        $status = $action === 'approve' ? 'approved' : 'rejected';
        $reason = trim((string) $request->input('rejection_reason', ''));

        DB::statement(
            "UPDATE hr_leaves 
             SET status = ?, approved_by = ?, rejection_reason = ?, updated_at = NOW() 
             WHERE id = ? AND tenant_id = ?",
            [$status, Auth::id(), $reason ?: null, $id, $tenantId]
        );

        audit('hr.leave_adjudicated', 'HrLeave', $id, ['status' => $status, 'employee' => $leave['employee_name']]);

        return back()->with('info', "Leave application for {$leave['employee_name']} marked as " . ucfirst($status) . ".");
    }

    public function payroll(Request $request): Response
    {
        $tenantId = TenantManager::id();
        $month = $request->query('month', date('Y-m'));

        // All active staff
        $staff = DB::select("SELECT id, name, title, role, email FROM users WHERE tenant_id = ? AND is_active = 1 ORDER BY name ASC", [$tenantId]);

        // Existing salary entries for this month
        $salaries = DB::select(
            "SELECT s.*, u.name AS employee_name, u.title AS employee_title, u.role AS employee_role
             FROM hr_salaries s
             JOIN users u ON u.id = s.user_id
             WHERE s.tenant_id = ? AND s.month_year = ?
             ORDER BY u.name ASC",
            [$tenantId, $month]
        );

        // Map by user_id for fast lookup
        $salaryMap = [];
        foreach ($salaries as $s) {
            $salaryMap[$s['user_id']] = $s;
        }

        // Totals for this month
        $totals = DB::selectOne(
            "SELECT COALESCE(SUM(basic_salary_minor), 0) AS total_basic,
                    COALESCE(SUM(allowances_minor), 0) AS total_allowances,
                    COALESCE(SUM(deductions_minor), 0) AS total_deductions,
                    COALESCE(SUM(net_salary_minor), 0) AS total_net,
                    COUNT(*) AS count_processed
             FROM hr_salaries
             WHERE tenant_id = ? AND month_year = ?",
            [$tenantId, $month]
        );

        return view('hr.payroll', [
            'title' => 'Salaries & Payroll Management',
            'month' => $month,
            'staff' => $staff,
            'salaries' => $salaries,
            'salaryMap' => $salaryMap,
            'totals' => $totals,
        ]);
    }

    public function processPayroll(Request $request): Response
    {
        $tenantId = TenantManager::id();

        $data = $this->validate($request, [
            'user_id'          => 'required|integer',
            'month_year'       => 'required|string|max:10',
            'basic_salary'     => 'required|numeric|min:0',
            'allowances'       => 'nullable|numeric|min:0',
            'deductions'       => 'nullable|numeric|min:0',
            'payment_method'   => 'required|string|in:bank_transfer,mpesa,cash,cheque',
            'status'           => 'required|string|in:pending,processed,paid',
            'notes'            => 'nullable|string|max:255',
        ]);

        $basicMinor = to_minor($data['basic_salary']);
        $allowancesMinor = to_minor($data['allowances'] ?? 0);
        $deductionsMinor = to_minor($data['deductions'] ?? 0);
        $netMinor = max(0, ($basicMinor + $allowancesMinor) - $deductionsMinor);

        $userId = (int) $data['user_id'];
        $month = trim($data['month_year']);
        $user = DB::selectOne("SELECT name FROM users WHERE id = ? AND tenant_id = ?", [$userId, $tenantId]);
        if (!$user) abort(404, 'Employee not found');

        $paidAt = $data['status'] === 'paid' ? now() : null;

        // Upsert salary record
        $existing = DB::selectOne("SELECT id FROM hr_salaries WHERE tenant_id = ? AND user_id = ? AND month_year = ?", [$tenantId, $userId, $month]);
        if ($existing) {
            DB::statement(
                "UPDATE hr_salaries 
                 SET basic_salary_minor = ?, allowances_minor = ?, deductions_minor = ?, net_salary_minor = ?, payment_method = ?, status = ?, paid_at = ?, notes = ?, updated_at = NOW()
                 WHERE id = ? AND tenant_id = ?",
                [
                    $basicMinor, $allowancesMinor, $deductionsMinor, $netMinor,
                    $data['payment_method'], $data['status'], $paidAt, trim($data['notes'] ?? ''),
                    $existing['id'], $tenantId
                ]
            );
        } else {
            DB::statement(
                "INSERT INTO hr_salaries (tenant_id, user_id, month_year, basic_salary_minor, allowances_minor, deductions_minor, net_salary_minor, payment_method, status, paid_at, notes, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())",
                [
                    $tenantId, $userId, $month,
                    $basicMinor, $allowancesMinor, $deductionsMinor, $netMinor,
                    $data['payment_method'], $data['status'], $paidAt, trim($data['notes'] ?? '')
                ]
            );
        }

        audit('hr.salary_recorded', 'HrSalary', null, ['employee' => $user['name'], 'month' => $month, 'net_minor' => $netMinor, 'status' => $data['status']]);

        return redirect(route('hr.payroll') . '?month=' . urlencode($month))->with('success', "Payroll record for {$user['name']} ({$month}) updated.");
    }

    public function expenses(Request $request): Response
    {
        $tenantId = TenantManager::id();
        $category = $request->query('category', '');
        $dateFrom = $request->query('date_from', date('Y-m-01'));
        $dateTo = $request->query('date_to', date('Y-m-d'));

        $where = "WHERE e.tenant_id = ? AND e.expense_date BETWEEN ? AND ?";
        $params = [$tenantId, $dateFrom, $dateTo];

        if ($category !== '') {
            $where .= " AND e.category = ?";
            $params[] = $category;
        }

        $expenses = DB::select(
            "SELECT e.*, u.name AS recorded_by_name
             FROM hospital_expenses e
             LEFT JOIN users u ON u.id = e.recorded_by_user_id
             $where
             ORDER BY e.expense_date DESC, e.id DESC",
            $params
        );

        $totals = DB::selectOne(
            "SELECT COALESCE(SUM(amount_minor), 0) AS total_amount, COUNT(*) AS count_expenses
             FROM hospital_expenses e
             $where",
            $params
        );

        $categories = [
            'medical_supplies'      => 'Medical Supplies & Consumables',
            'pharmaceuticals'       => 'Pharmaceutical Purchases',
            'utilities'             => 'Electricity, Water & Internet',
            'equipment_maintenance' => 'Biomedical Equipment & Repairs',
            'salaries'              => 'Staff Salaries & Advances',
            'rent'                  => 'Hospital Facility Rent',
            'cleaning'              => 'Sanitization & Waste Disposal',
            'administrative'        => 'Legal, Software & Admin',
            'other'                 => 'Other Operational Expenses',
        ];

        return view('hr.expenses', [
            'title' => 'Hospital Operating Expenses',
            'expenses' => $expenses,
            'totals' => $totals,
            'categories' => $categories,
            'currentCategory' => $category,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ]);
    }

    public function storeExpense(Request $request): Response
    {
        $tenantId = TenantManager::id();

        $data = $this->validate($request, [
            'category'       => 'required|string',
            'title'          => 'required|string|max:150',
            'amount'         => 'required|numeric|min:0.01',
            'payment_method' => 'required|string|in:cash,mpesa,bank_transfer,cheque,card',
            'vendor_name'    => 'nullable|string|max:150',
            'receipt_no'     => 'nullable|string|max:60',
            'expense_date'   => 'required|date',
            'notes'          => 'nullable|string|max:500',
        ]);

        $amountMinor = to_minor($data['amount']);

        DB::statement(
            "INSERT INTO hospital_expenses (tenant_id, category, title, amount_minor, payment_method, vendor_name, receipt_no, expense_date, recorded_by_user_id, status, notes, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'approved', ?, NOW(), NOW())",
            [
                $tenantId,
                $data['category'],
                trim($data['title']),
                $amountMinor,
                $data['payment_method'],
                trim($data['vendor_name'] ?? ''),
                trim($data['receipt_no'] ?? ''),
                $data['expense_date'],
                Auth::id(),
                trim($data['notes'] ?? ''),
            ]
        );

        audit('hr.expense_recorded', 'HospitalExpense', null, ['title' => $data['title'], 'amount_minor' => $amountMinor, 'category' => $data['category']]);

        return redirect(route('hr.expenses'))->with('success', "Expense '{$data['title']}' recorded successfully.");
    }
}
