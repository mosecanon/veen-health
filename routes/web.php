<?php
/** @var \Veen\Core\Router $router */

use App\Controllers\AppointmentController;
use App\Controllers\AuthController;
use App\Controllers\BillingController;
use App\Controllers\BranchController;
use App\Controllers\CccClinicController;
use App\Controllers\ConsultationController;
use App\Controllers\DashboardController;
use App\Controllers\DisplayController;
use App\Controllers\HrController;
use App\Controllers\InpatientController;
use App\Controllers\KidsClinicController;
use App\Controllers\LabController;
use App\Controllers\MaternalClinicController;
use App\Controllers\MortuaryController;
use App\Controllers\PatientController;
use App\Controllers\PharmacyController;
use App\Controllers\PharmacyPosController;
use App\Controllers\PharmacySessionController;
use App\Controllers\PlatformController;
use App\Controllers\QueueController;
use App\Controllers\RadiologyController;
use App\Controllers\ReportController;
use App\Controllers\SettingController;
use App\Controllers\StaffController;

// ---------- Public / Auth ----------
$router->get('/login', [AuthController::class, 'showLogin'])->name('login');
$router->post('/login', [AuthController::class, 'login'])->name('login.post');
$router->post('/logout', [AuthController::class, 'logout'])->name('logout');

// ---------- Public TV Display Screens (Token-authenticated) ----------
$router->get('/display/{token}', [DisplayController::class, 'show'])->name('display.show');
$router->get('/display/{token}/poll', [DisplayController::class, 'poll'])->name('display.poll');

// ---------- Platform Super Admin (Veen Africa) ----------
$router->group(['prefix' => 'platform', 'middleware' => ['auth', 'super'], 'as' => 'platform.'], function ($r) {
    $r->get('/', [PlatformController::class, 'index'])->name('dashboard');
    $r->post('/tenants', [PlatformController::class, 'createTenant'])->name('tenants.store');
    $r->post('/tenants/{id}/toggle', [PlatformController::class, 'toggleStatus'])->name('tenants.toggle');
    $r->post('/tenants/{id}/modules', [PlatformController::class, 'updateModules'])->name('tenants.modules');
    $r->post('/switch-tenant/{id}', [PlatformController::class, 'switchTenant'])->name('switch_tenant');
    $r->get('/exit-tenant', [PlatformController::class, 'exitTenant'])->name('exit_tenant');
    $r->get('/plans', [PlatformController::class, 'plans'])->name('plans');
    $r->post('/plans', [PlatformController::class, 'storePlan'])->name('plans.store');
    $r->post('/cache/flush', [PlatformController::class, 'flushGlobalCache'])->name('cache.flush');
});

// ---------- Tenant Hospital Workspace ----------
$router->group(['middleware' => ['auth', 'tenant']], function ($r) {
    // Dashboard
    $r->get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Clinical Consultations & EMR Encounters
    $r->get('/consultations', [ConsultationController::class, 'index'])->name('consultations.index');
    $r->get('/consultations/start', [ConsultationController::class, 'start'])->name('consultations.start.get');
    $r->post('/consultations/start', [ConsultationController::class, 'start'])->name('consultations.start');
    $r->get('/consultations/{id}', [ConsultationController::class, 'show'])->name('consultations.show');
    $r->post('/consultations/{id}/vitals', [ConsultationController::class, 'updateVitals'])->name('consultations.vitals');
    $r->post('/consultations/{id}/notes', [ConsultationController::class, 'updateNotes'])->name('consultations.notes');
    $r->post('/consultations/{id}/lab/order', [ConsultationController::class, 'orderLab'])->name('consultations.lab.order');
    $r->post('/consultations/{id}/rx/add', [ConsultationController::class, 'addPrescription'])->name('consultations.rx.add');
    $r->post('/consultations/{id}/complete', [ConsultationController::class, 'complete'])->name('consultations.complete');

    // Kids Clinic & Child Welfare (CWC)
    $r->get('/clinic/kids', [KidsClinicController::class, 'index'])->name('clinic.kids.index');
    $r->post('/clinic/kids/profile', [KidsClinicController::class, 'storeProfile'])->name('clinic.kids.profile.store');
    $r->get('/clinic/kids/{id}', [KidsClinicController::class, 'show'])->name('clinic.kids.show');
    $r->post('/clinic/kids/{id}/profile', [KidsClinicController::class, 'updateProfile'])->name('clinic.kids.profile.update');
    $r->post('/clinic/kids/{id}/visits', [KidsClinicController::class, 'storeVisit'])->name('clinic.kids.visits.store');
    $r->post('/clinic/kids/{id}/visit', [KidsClinicController::class, 'storeVisit'])->name('clinic.kids.visit.store');
    $r->post('/clinic/kids/immunizations/{id}', [KidsClinicController::class, 'updateImmunization'])->name('clinic.kids.immunizations.update');
    $r->get('/clinic/kids/{id}/card', [KidsClinicController::class, 'card'])->name('clinic.kids.card');

    // Clinic for Pregnant Women (Antenatal Care - ANC & Maternal Health)
    $r->get('/clinic/maternal', [MaternalClinicController::class, 'index'])->name('clinic.maternal.index');
    $r->post('/clinic/maternal/profile', [MaternalClinicController::class, 'storeProfile'])->name('clinic.maternal.profile.store');
    $r->get('/clinic/maternal/{id}', [MaternalClinicController::class, 'show'])->name('clinic.maternal.show');
    $r->post('/clinic/maternal/{id}/profile', [MaternalClinicController::class, 'updateProfile'])->name('clinic.maternal.profile.update');
    $r->post('/clinic/maternal/{id}/visits', [MaternalClinicController::class, 'storeVisit'])->name('clinic.maternal.visits.store');
    $r->post('/clinic/maternal/{id}/visit', [MaternalClinicController::class, 'storeVisit'])->name('clinic.maternal.visit.store');
    $r->post('/clinic/maternal/{id}/birth-plan', [MaternalClinicController::class, 'updateBirthPlan'])->name('clinic.maternal.birth_plan.update');
    $r->post('/clinic/maternal/{id}/delivery', [MaternalClinicController::class, 'concludeDelivery'])->name('clinic.maternal.delivery.conclude');
    $r->get('/clinic/maternal/{id}/card', [MaternalClinicController::class, 'card'])->name('clinic.maternal.card');

    // HIV / Comprehensive Care Centre (CCC)
    $r->get('/clinic/ccc', [CccClinicController::class, 'index'])->name('clinic.ccc.index');
    $r->post('/clinic/ccc', [CccClinicController::class, 'store'])->name('clinic.ccc.store');
    $r->get('/clinic/ccc/{id}', [CccClinicController::class, 'show'])->name('clinic.ccc.show');
    $r->post('/clinic/ccc/{id}/visit', [CccClinicController::class, 'storeVisit'])->name('clinic.ccc.visit.store');
    $r->post('/clinic/ccc/{id}/visits', [CccClinicController::class, 'storeVisit'])->name('clinic.ccc.visits.store');
    $r->get('/clinic/ccc/{id}/card', [CccClinicController::class, 'card'])->name('clinic.ccc.card');

    // Laboratory (LIMS)
    $r->get('/lab', [LabController::class, 'index'])->name('lab.index');
    $r->post('/lab/results/{id}/collect', [LabController::class, 'collectSpecimen'])->name('lab.specimen.collect');
    $r->post('/lab/results/{id}/record', [LabController::class, 'recordResult'])->name('lab.results.record');
    $r->post('/lab/verify/{id}', [LabController::class, 'verifyResult'])->name('lab.verify');

    // Radiology & Diagnostic Imaging (MRI, CT, X-Ray)
    $r->get('/radiology', [RadiologyController::class, 'index'])->name('radiology.index');
    $r->post('/radiology', [RadiologyController::class, 'store'])->name('radiology.store');
    $r->get('/radiology/{id}', [RadiologyController::class, 'show'])->name('radiology.show');
    $r->post('/radiology/{id}/transport', [RadiologyController::class, 'updateTransport'])->name('radiology.transport.update');
    $r->post('/radiology/{id}/complete', [RadiologyController::class, 'complete'])->name('radiology.complete');
    $r->get('/radiology/{id}/report', [RadiologyController::class, 'report'])->name('radiology.report');

    // Inpatient Wards & Beds (IPD)
    $r->get('/inpatient', [InpatientController::class, 'index'])->name('inpatient.index');
    $r->post('/inpatient/wards', [InpatientController::class, 'storeWard'])->name('inpatient.wards.store');
    $r->post('/inpatient/admissions', [InpatientController::class, 'admit'])->name('inpatient.admit');
    $r->post('/inpatient/admissions/{id}/discharge', [InpatientController::class, 'discharge'])->name('inpatient.discharge');

    // Mortuary & Deceased Registry
    $r->get('/mortuary', [MortuaryController::class, 'index'])->name('mortuary.index');
    $r->post('/mortuary', [MortuaryController::class, 'store'])->name('mortuary.store');
    $r->get('/mortuary/{id}', [MortuaryController::class, 'show'])->name('mortuary.show');
    $r->post('/mortuary/{id}/preservation', [MortuaryController::class, 'updatePreservation'])->name('mortuary.preservation.update');
    $r->post('/mortuary/{id}/autopsy', [MortuaryController::class, 'recordAutopsy'])->name('mortuary.autopsy.store');
    $r->post('/mortuary/{id}/release', [MortuaryController::class, 'releaseBody'])->name('mortuary.release.store');
    $r->get('/mortuary/{id}/tag', [MortuaryController::class, 'tag'])->name('mortuary.tag');
    $r->get('/mortuary/{id}/certificate', [MortuaryController::class, 'certificate'])->name('mortuary.certificate');

    // Billing, Invoicing & Claims
    $r->get('/billing', [BillingController::class, 'index'])->name('billing.index');
    $r->post('/billing/invoices', [BillingController::class, 'createInvoice'])->name('billing.create');
    $r->get('/billing/invoices/{id}', [BillingController::class, 'show'])->name('billing.show');
    $r->post('/billing/invoices/{id}/items', [BillingController::class, 'addItem'])->name('billing.items.add');
    $r->post('/billing/invoices/{id}/pay', [BillingController::class, 'recordPayment'])->name('billing.pay');
    $r->get('/billing/claims', [BillingController::class, 'claims'])->name('billing.claims');
    $r->post('/billing/claims', [BillingController::class, 'submitClaim'])->name('billing.claims.submit');
    $r->post('/billing/claims/{id}/adjudicate', [BillingController::class, 'adjudicateClaim'])->name('billing.claims.adjudicate');

    // Hospital Staff & Employees Directory
    $r->get('/staff', [StaffController::class, 'index'])->name('staff.index');
    $r->post('/staff', [StaffController::class, 'store'])->name('staff.store');
    $r->post('/staff/{id}', [StaffController::class, 'update'])->name('staff.update');
    $r->post('/staff/{id}/toggle', [StaffController::class, 'toggle'])->name('staff.toggle');
    $r->post('/staff/{id}/reset-password', [StaffController::class, 'resetPassword'])->name('staff.reset_password');
    $r->get('/staff/roles', [StaffController::class, 'roles'])->name('staff.roles');
    $r->post('/staff/roles', [StaffController::class, 'storeRole'])->name('staff.roles.store');
    $r->post('/staff/roles/{id}', [StaffController::class, 'updateRole'])->name('staff.roles.update');
    $r->post('/staff/roles/{id}/delete', [StaffController::class, 'deleteRole'])->name('staff.roles.delete');

    // Human Resources (HR), Payroll & Expenses
    $r->get('/hr', [HrController::class, 'index'])->name('hr.index');
    $r->get('/hr/leaves', [HrController::class, 'leaves'])->name('hr.leaves');
    $r->post('/hr/leaves', [HrController::class, 'storeLeave'])->name('hr.leaves.store');
    $r->post('/hr/leaves/{id}/status', [HrController::class, 'updateLeaveStatus'])->name('hr.leaves.status');
    $r->get('/hr/payroll', [HrController::class, 'payroll'])->name('hr.payroll');
    $r->post('/hr/payroll', [HrController::class, 'processPayroll'])->name('hr.payroll.store');
    $r->get('/hr/expenses', [HrController::class, 'expenses'])->name('hr.expenses');
    $r->post('/hr/expenses', [HrController::class, 'storeExpense'])->name('hr.expenses.store');

    // Patients
    $r->get('/patients', [PatientController::class, 'index'])->name('patients.index');
    $r->get('/patients/create', [PatientController::class, 'create'])->name('patients.create');
    $r->post('/patients', [PatientController::class, 'store'])->name('patients.store');
    $r->post('/patients/quick-store', [PatientController::class, 'quickStore'])->name('patients.quick_store');
    $r->get('/patients/search', [PatientController::class, 'search'])->name('patients.search');
    $r->get('/patients/{id}', [PatientController::class, 'show'])->name('patients.show');
    $r->get('/patients/{id}/edit', [PatientController::class, 'edit'])->name('patients.edit');
    $r->post('/patients/{id}', [PatientController::class, 'update'])->name('patients.update');
    $r->post('/patients/{id}/delete', [PatientController::class, 'destroy'])->name('patients.delete');
    $r->post('/patients/{id}/restore', [PatientController::class, 'restore'])->name('patients.restore');
    $r->get('/patients/{id}/card', [PatientController::class, 'card'])->name('patients.card');

    // Queue & Ticketing
    $r->get('/queue', [QueueController::class, 'index'])->name('queue.index');
    $r->get('/queue/kiosk', [QueueController::class, 'kiosk'])->name('queue.kiosk');
    $r->post('/queue/tickets', [QueueController::class, 'issueTicket'])->name('queue.tickets.issue');
    $r->get('/queue/tickets/{id}/print', [QueueController::class, 'printTicket'])->name('queue.tickets.print');
    $r->get('/queue/service-points/{id}', [QueueController::class, 'console'])->name('queue.console');
    $r->post('/queue/service-points/{id}/call-next', [QueueController::class, 'callNext'])->name('queue.call_next');
    $r->post('/queue/tickets/{id}/recall', [QueueController::class, 'recall'])->name('queue.recall');
    $r->post('/queue/tickets/{id}/start', [QueueController::class, 'start'])->name('queue.start');
    $r->post('/queue/tickets/{id}/complete', [QueueController::class, 'complete'])->name('queue.complete');
    $r->post('/queue/tickets/{id}/skip', [QueueController::class, 'skip'])->name('queue.skip');
    $r->post('/queue/tickets/{id}/transfer', [QueueController::class, 'transfer'])->name('queue.transfer');

    // Pharmacy Inventory & Restock Approvals
    $r->get('/pharmacy', [PharmacyController::class, 'index'])->name('pharmacy.index');
    $r->get('/pharmacy/products/create', [PharmacyController::class, 'createProduct'])->name('pharmacy.products.create');
    $r->post('/pharmacy/products', [PharmacyController::class, 'storeProduct'])->name('pharmacy.products.store');
    $r->get('/pharmacy/batches', [PharmacyController::class, 'batchesIndex'])->name('pharmacy.batches.index');
    $r->get('/pharmacy/batches/receive', [PharmacyController::class, 'receiveBatchForm'])->name('pharmacy.batches.receive');
    $r->post('/pharmacy/batches/receive', [PharmacyController::class, 'receiveBatch'])->name('pharmacy.batches.store');
    $r->get('/pharmacy/batches/{id}/edit', [PharmacyController::class, 'editBatch'])->name('pharmacy.batches.edit');
    $r->post('/pharmacy/batches/{id}', [PharmacyController::class, 'updateBatch'])->name('pharmacy.batches.update');
    $r->post('/pharmacy/batches/{id}/approve', [PharmacyController::class, 'approveBatch'])->name('pharmacy.batches.approve');
    $r->post('/pharmacy/batches/{id}/reject', [PharmacyController::class, 'rejectBatch'])->name('pharmacy.batches.reject');

    // Pharmacy Suppliers Management & Supply Analytics
    $r->get('/pharmacy/suppliers', [PharmacyController::class, 'suppliers'])->name('pharmacy.suppliers.index');
    $r->post('/pharmacy/suppliers', [PharmacyController::class, 'storeSupplier'])->name('pharmacy.suppliers.store');
    $r->post('/pharmacy/suppliers/quick-store', [PharmacyController::class, 'quickStoreSupplier'])->name('pharmacy.suppliers.quick_store');
    $r->post('/pharmacy/suppliers/{id}', [PharmacyController::class, 'updateSupplier'])->name('pharmacy.suppliers.update');
    $r->post('/pharmacy/suppliers/{id}/delete', [PharmacyController::class, 'deleteSupplier'])->name('pharmacy.suppliers.delete');

    // Pharmacy Categories Management
    $r->get('/pharmacy/categories', [PharmacyController::class, 'categories'])->name('pharmacy.categories.index');
    $r->post('/pharmacy/categories', [PharmacyController::class, 'storeCategory'])->name('pharmacy.categories.store');
    $r->post('/pharmacy/categories/quick-store', [PharmacyController::class, 'quickStoreCategory'])->name('pharmacy.categories.quick_store');
    $r->post('/pharmacy/categories/{id}/delete', [PharmacyController::class, 'deleteCategory'])->name('pharmacy.categories.delete');

    // Pharmacy POS (OTC Sales)
    $r->get('/pharmacy/pos', [PharmacyPosController::class, 'index'])->name('pharmacy.pos');
    $r->get('/pharmacy/pos/search', [PharmacyPosController::class, 'searchProducts'])->name('pharmacy.pos.search');
    $r->post('/pharmacy/pos/checkout', [PharmacyPosController::class, 'checkout'])->name('pharmacy.pos.checkout');
    $r->get('/pharmacy/sales', [PharmacyController::class, 'salesReport'])->name('pharmacy.sales');
    $r->get('/pharmacy/stock-movements', [PharmacyController::class, 'stockMovements'])->name('pharmacy.stock_movements');
    $r->get('/pharmacy/sales/{id}/receipt', [PharmacyPosController::class, 'receipt'])->name('pharmacy.receipt');

    // Pharmacy Register Sessions
    $r->get('/pharmacy/sessions', [PharmacySessionController::class, 'index'])->name('pharmacy.sessions.index');
    $r->post('/pharmacy/sessions/open', [PharmacySessionController::class, 'open'])->name('pharmacy.sessions.open');
    $r->post('/pharmacy/sessions/{id}/close', [PharmacySessionController::class, 'close'])->name('pharmacy.sessions.close');

    // Appointments & Reservations Calendar
    $r->get('/appointments', [AppointmentController::class, 'index'])->name('appointments.index');
    $r->post('/appointments', [AppointmentController::class, 'store'])->name('appointments.store');
    $r->post('/appointments/{id}/status', [AppointmentController::class, 'updateStatus'])->name('appointments.status');
    $r->get('/appointments/reminders', [AppointmentController::class, 'todayReminders'])->name('appointments.reminders');

    // Comprehensive Analytics & Financial Year Reports
    $r->get('/reports', [ReportController::class, 'index'])->name('reports.index');

    // Hospital Settings, Facility Branding & User Profile
    $r->get('/settings', [SettingController::class, 'index'])->name('settings.index');
    $r->post('/settings/company', [SettingController::class, 'updateCompany'])->name('settings.company');
    $r->post('/settings/profile', [SettingController::class, 'updateProfile'])->name('settings.profile');
    $r->post('/settings/password', [SettingController::class, 'updatePassword'])->name('settings.password');
    $r->post('/settings/cache/flush', [SettingController::class, 'flushCache'])->name('settings.cache.flush');

    // Multi-Branch Management & Switching
    $r->get('/branches', [BranchController::class, 'index'])->name('branches.index');
    $r->post('/branches', [BranchController::class, 'store'])->name('branches.store');
    $r->post('/branches/{id}', [BranchController::class, 'update'])->name('branches.update');
    $r->post('/branches/{id}/toggle', [BranchController::class, 'toggle'])->name('branches.toggle');
    $r->post('/branches/switch/{id}', [BranchController::class, 'switchBranch'])->name('branches.switch');
});
