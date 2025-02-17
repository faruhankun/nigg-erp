<?php

namespace App\Http\Controllers;

use App\Models\InboundRequest;
use App\Models\Purchase;
use App\Models\User;
use App\Models\Location;
use App\Models\Inventory;
use App\Models\InventoryHistory;
use Illuminate\Http\Request;

class InboundRequestController extends Controller
{
    // Show all inbound requests
    public function index()
    {
        $inboundRequests = InboundRequest::with('purchase', 'warehouse')->orderBy('created_at', 'desc')->get();
        return view('inbound_requests.index', compact('inboundRequests'));
    }

    // Create inbound request for a purchase
    public function create($purchaseId)
    {
        $purchase = Purchase::with('products')->findOrFail($purchaseId);
        return view('inbound_requests.create', compact('purchase'));
    }


    // Store inbound request
    public function store(Request $request)
    {
        $request->validate([
            'purchase_order_id' => 'required|exists:purchases,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'received_quantities' => 'required|array',
            'received_quantities.*' => 'required|integer|min:0',
        ]);

        $inboundRequest = InboundRequest::create([
            'purchase_order_id' => $request->purchase_order_id,
            'warehouse_id' => $request->warehouse_id,
            'received_quantities' => $request->received_quantities,
            'status' => 'In Transit',
            'notes' => $request->notes,
        ]);

        return redirect()->route('inbound_requests.index')->with('success', 'Inbound request created successfully.');
    }
	

	public function show($id)
	{
		$inboundRequest = InboundRequest::with('warehouse', 'purchase.products')->findOrFail($id);

		return view('inbound_requests.show', compact('inboundRequest'));
	}


	// Edit method to show the edit view
    public function edit($id)
    {
        $inboundRequest = InboundRequest::with('purchase.products', 'warehouse')->findOrFail($id);
        $users = User::all(); // Assuming you want to select from all users

        return view('inbound_requests.edit', compact('inboundRequest', 'users'));
    }


	// Update method to handle form submission
    public function update(Request $request, $id)
    {
        $request->validate([
			'status' => 'required',
			'verified_by' => 'nullable|exists:users,id',
			'notes' => 'nullable|string',
			'received_quantities' => 'array',
			'received_quantities.*' => 'integer|min:0',
			'arrival_date' => 'nullable|date',  // Add this line
		]);


        $inboundRequest = InboundRequest::findOrFail($id);
		
		// Handle checking quantities action
		if ($request->action === 'check_quantities') {
			$hasDiscrepancy = false;
			$receivedQuantities = $request->input('received_quantities', []);

			// Check for discrepancies
			foreach ($receivedQuantities as $productId => $receivedQuantity) {
				$requestedQuantity = $inboundRequest->requested_quantities[$productId] ?? 0;
				if ($requestedQuantity != $receivedQuantity) {
					$hasDiscrepancy = true;
					break;
				}
			}

			// Set status based on discrepancy
			if ($hasDiscrepancy) {
				$inboundRequest->status = 'Quantity Discrepancy';
				$inboundRequest->notes = 'Quantity discrepancy detected. Awaiting purchase team decision.';
			} else {
				$inboundRequest->status = 'Ready to Complete';
				$inboundRequest->notes = 'Quantities match; ready for completion.';
			}

			$inboundRequest->received_quantities = $receivedQuantities;
			$inboundRequest->save();

			return redirect()->route('inbound_requests.show', $inboundRequest->id)
				->with('success', 'Quantities checked successfully.');
		}
		
        $inboundRequest->update([
			'status' => $request->status,
			'verified_by' => $request->verified_by,
			'notes' => $request->notes,
			'received_quantities' => $request->input('received_quantities', []),
			'arrival_date' => $request->arrival_date,
		]);

		// Check if arrival_date is set and status is In Transit, then update status to Received - Pending Verification
		if ($request->arrival_date && $inboundRequest->status == 'In Transit') {
			$inboundRequest->status = 'Received - Pending Verification';
			$inboundRequest->save();
		}

		// Check overall status of all inbound requests for this purchase
		$this->updatePurchaseStatus($inboundRequest->purchase_order_id);

        return redirect()->route('inbound_requests.index')
            ->with('success', 'Inbound request updated successfully.');
    }
	
	
	public function handleDiscrepancyAction($id, Request $request)
	{
		$inboundRequest = InboundRequest::with('purchase')->findOrFail($id);
		$action = $request->input('action');

		switch ($action) {
			case 'accept_partial':
				// Accept received quantity as-is, mark discrepancy as resolved
				$inboundRequest->status = 'Ready to Complete';
				$inboundRequest->notes = 'Discrepancy resolved by accepting partial shipment.';
				break;

			case 'request_additional':
				// Create a new inbound request for the remaining quantities
				InboundRequest::create([
					'purchase_order_id' => $inboundRequest->purchase_order_id,
					'warehouse_id' => $inboundRequest->warehouse_id,
					'requested_quantities' => ($this->getRemainingQuantities($inboundRequest)),
					'received_quantities' => ([]), // Start with an empty JSON array
					'status' => 'In Transit',
					'notes' => 'Additional inbound request for remaining quantities'
				]);

				// Update purchase status to indicate pending additional shipment
				$inboundRequest->status = 'Ready to Complete';
				$inboundRequest->notes = 'Additional inbound request created for missing items. Dicrepancy resolved';
				break;

			case 'record_excess':
				// Logic for recording excess as extra stock
				$inboundRequest->status = 'Ready to Complete';
				$inboundRequest->save();
				// Additional logic to add the excess to inventory could go here
				break;

			default:
				return redirect()->back()->with('error', 'Invalid action');
		}

		$inboundRequest->save();

		// Check overall status of all inbound requests for this purchase
		$this->updatePurchaseStatus($inboundRequest->purchase_order_id);

		return redirect()->route('purchases.show', $inboundRequest->purchase_order_id)->with('success', 'Discrepancy handled successfully.');
	}

	// Helper function to calculate remaining quantities for a new inbound request
	private function getRemainingQuantities($inboundRequest)
	{
		$requestedQuantities = ($inboundRequest->requested_quantities);
		$receivedQuantities = ($inboundRequest->received_quantities);

		$remainingQuantities = [];
		foreach ($requestedQuantities as $productId => $quantity) {
			$receivedQty = $receivedQuantities[$productId] ?? 0;
			if ($quantity > $receivedQty) {
				$remainingQuantities[$productId] = $quantity - $receivedQty;
			}
		}

		return $remainingQuantities;
	}

	// Helper function to update purchase status based on inbound requests
	private function updatePurchaseStatus($purchaseId)
	{
		$purchase = Purchase::with('inboundRequests')->findOrFail($purchaseId);
		$inboundRequests = $purchase->inboundRequests;

		if ($inboundRequests->every(fn($ir) => $ir->status === 'Completed')) {
			// All inbound requests are completed
			$purchase->status = 'Completed';
		} elseif ($inboundRequests->contains('status', 'Quantity Discrepancy')) {
			// If any request has a quantity discrepancy
			$purchase->status = 'Quantity Discrepancy';
		} elseif ($inboundRequests->contains('status', 'In Transit') && $inboundRequests->contains('status', 'Completed')) {
			// There’s at least one completed request, but some are still in transit
			$purchase->status = 'Pending Additional Shipment';
		} elseif ($inboundRequests->every(fn($ir) => $ir->arrival_date)) {
			// All inbound received are completed
			$purchase->status = 'Received - Pending Verification';
		} else {
			// Default to "In Transit" if none of the above conditions are met
			$purchase->status = 'In Transit';
		}

		$purchase->save();
	}


	public function complete($id)
	{
		$inboundRequest = InboundRequest::with('purchase.products')->findOrFail($id);
		return view('inbound_requests.complete', compact('inboundRequest'));
	}


	public function storeCompletion($id, Request $request)
	{
		$inboundRequest = InboundRequest::findOrFail($id);

		// Loop through each product's assigned location
		foreach ($request->input('locations') as $productId => $locationData) {
			$location = Location::where('warehouse_id', $inboundRequest->warehouse_id)
								->where('room', $locationData['room'])
								->where('rack', $locationData['rack'])
								->first();

			if (!$location) {
				return back()->withErrors(['error' => 'Invalid room or rack selected for product ID ' . $productId]);
			}

			// Record each product in inventory history
			InventoryHistory::create([
				'product_id' => $productId,
				'warehouse_id' => $inboundRequest->warehouse_id,
				'location_id' => $location->id,
				'quantity' => $inboundRequest->received_quantities[$productId] ?? 0,
				'transaction_type' => 'Inbound',
				'notes' => 'Transferred from inbound request ' . $inboundRequest->id,
			]);
			
			
			// Check if the product already exists in the inventory for the specified warehouse and location
			$inventory = Inventory::where('product_id', $productId)
								  ->where('warehouse_id', $inboundRequest->warehouse_id)
								  ->where('location_id', $location->id)
								  ->first();
			if ($inventory) {
				// If the inventory entry exists, update the quantity
				$receivedQuantity = $inboundRequest->received_quantities[$productId] ?? 0;
				$inventory->quantity += $receivedQuantity;
				$inventory->save();
			} else {
				// If no inventory entry exists for this location, create a new one
				Inventory::create([
					'product_id' => $productId,
					'warehouse_id' => $inboundRequest->warehouse_id,
					'location_id' => $location->id,
					'quantity' => $inboundRequest->received_quantities[$productId] ?? 0,
				]);
			}
		}

		// Mark the inbound request as completed
		$inboundRequest->status = 'Completed';
		$inboundRequest->save();

		// Check overall status of all inbound requests for this purchase
		$this->updatePurchaseStatus($inboundRequest->purchase_order_id);
	
		return redirect()->route('inbound_requests.show', $id)->with('success', 'Inbound request completed.');
	}


}
