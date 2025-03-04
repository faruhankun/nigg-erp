<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\CompanyMiddleware;

use App\Http\Controllers\CompanyUserController;
use App\Http\Controllers\CompanyRoleController;
use App\Http\Controllers\CompanyPermissionController;

use App\Http\Controllers\EmployeeController;

use App\Http\Controllers\Company\PurchaseController;
use App\Http\Controllers\Company\PurchaseInvoiceController;
use App\Http\Controllers\SupplierController;

use App\Http\Controllers\Company\SaleController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CustomerComplaintController;

use App\Http\Controllers\ProductController;
use App\Http\Controllers\Company\WarehouseLocationController;
use App\Http\Controllers\Company\InventoryTransferController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\Company\InboundController;
use App\Http\Controllers\Company\OutboundController;

use App\Http\Controllers\Company\ShipmentController;
use App\Http\Controllers\Company\CourierController;

// Company
Route::middleware(['auth', 
                CompanyMiddleware::class,
])->group(function () {
    Route::resource('company_users', CompanyUserController::class);
    Route::delete('/company_users/cancelInvite/{id}', [CompanyUserController::class, 'cancelInvite'])->name('company_users.cancelInvite');

    Route::resource('company_roles', CompanyRoleController::class);
    Route::get('/company_roles', [CompanyRoleController::class, 'index'])->name('company_roles.index');
    Route::get('/company_roles/data', [CompanyRoleController::class, 'getRolesData'])->name('company_roles.data');
    
    Route::resource('company_permissions', CompanyPermissionController::class);
    
    Route::resource('employees', EmployeeController::class);
    

    route::resource("purchases", PurchaseController::class);
    Route::post('purchases/{purchases}/action/{action}', [PurchaseController::class, 'handleAction'])->name('purchases.action');
    Route::get('purchases/{id}/duplicate', [PurchaseController::class, 'duplicate'])->name('purchases.duplicate');
    Route::resource('purchase_invoices', PurchaseInvoiceController::class);
    Route::resource('suppliers', SupplierController::class);
    

    route::resource("customers", CustomerController::class);
    Route::resource('sales', SaleController::class);
    Route::get('sales/{sale}/status/{status}', [SaleController::class, 'updateStatus'])->name('sales.updateStatus');
    Route::resource('customer_complaints', CustomerComplaintController::class);
    Route::put('customer_complaints/{customer_complaint}/resolve', [CustomerComplaintController::class, 'resolve'])->name('customer_complaints.resolve');
    

    route::resource("products", controller: ProductController::class);
    route::resource("warehouse_locations", WarehouseLocationController::class);

    route::resource("inventory_transfers", controller: InventoryTransferController::class);
    Route::post('inventory_transfers/{id}/action/{action}', [InventoryTransferController::class, 'handleAction'])->name('inventory_transfers.action');
    Route::post('inventory_transfers/storeRequest', [InventoryTransferController::class, 'storeRequest'])->name('inventory_transfers.storeRequest');

    route::resource("inbounds", controller: InboundController::class);
    route::resource("outbounds", controller: OutboundController::class);
    Route::resource('inventory', InventoryController::class)->except(['show']);
    Route::get('/inventory/adjust', [InventoryController::class, 'adjust'])->name('inventory.adjust');
    Route::get('/inventory/history', [InventoryController::class, 'history'])->name('inventory.history');


    Route::resource("shipments", ShipmentController::class);
    Route::post('shipments/{shipments}/action/{action}', [ShipmentController::class, 'handleAction'])->name('shipments.action');
    Route::get('shipments/{id}/confirm', [ShipmentController::class, 'confirm'])->name('shipments.confirm');
    Route::put('shipments/{id}/confirm-update', [ShipmentController::class, 'confirm_update'])->name('shipments.confirm-update');
    Route::get('shipments/{id}/confirm-show', [ShipmentController::class, 'confirm_show'])->name('shipments.confirm-show');
    Route::resource("couriers", CourierController::class);
});
