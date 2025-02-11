<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Policy;
use App\Models\PolicyHolder;
use App\Models\Address;
use App\Models\Driver;
use App\Models\Vehicle;
use App\Models\GaragingAddress;
use App\Models\Coverage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;


class PolicyController extends Controller
{

    // Store a new policy
    public function store(Request $request)
    {
        Log::info('Received request:', $request->all());

        $request->validate([
            'policyNo' => 'required|string|unique:policies,policy_no',
            'policyStatus' => 'required|string',
            'policyType' => 'required|string',
            'policyEffectiveDate' => 'required|date',
            'policyExpirationDate' => 'required|date',
            'policyHolder' => 'required|array',
            'policyHolder.firstName' => 'required|string',
            'policyHolder.lastName' => 'required|string',
            'policyHolder.address' => 'required|array',
            'policyHolder.address.street' => 'required|string',
            'policyHolder.address.city' => 'required|string',
            'policyHolder.address.state' => 'required|string',
            'policyHolder.address.zip' => 'required|string',
            'drivers' => 'nullable|array',
            'drivers.*.firstName' => 'required|string',
            'drivers.*.lastName' => 'required|string',
            'drivers.*.age' => 'required|integer',
            'drivers.*.gender' => 'required|string',
            'drivers.*.maritalStatus' => 'required|string',
            'drivers.*.licenseNumber' => 'required|string',
            'drivers.*.licenseState' => 'required|string',
            'drivers.*.licenseStatus' => 'required|string',
            'drivers.*.licenseEffectiveDate' => 'required|date',
            'drivers.*.licenseExpirationDate' => 'required|date',
            'drivers.*.licenseClass' => 'required|string',
            'vehicles' => 'nullable|array',
            'vehicles.*.year' => 'required|integer',
            'vehicles.*.make' => 'required|string',
            'vehicles.*.model' => 'required|string',
            'vehicles.*.vin' => 'required|string',
            'vehicles.*.usage' => 'required|string',
            'vehicles.*.primaryUse' => 'required|string',
            'vehicles.*.annualMileage' => 'required|integer',
            'vehicles.*.ownership' => 'required|string',
            'vehicles.*.garagingAddress' => 'required|array',
            'vehicles.*.garagingAddress.street' => 'required|string',
            'vehicles.*.garagingAddress.city' => 'required|string',
            'vehicles.*.garagingAddress.state' => 'required|string',
            'vehicles.*.garagingAddress.zip' => 'required|string',
            'vehicles.*.coverages' => 'nullable|array',
            'vehicles.*.coverages.*.type' => 'required|string',
            'vehicles.*.coverages.*.limit' => 'required|integer',
            'vehicles.*.coverages.*.deductible' => 'required|integer',
        ]);

        DB::beginTransaction();
        try {

            $policy = Policy::create([
                'policy_no' => $request->policyNo,
                'policy_status' => $request->policyStatus,
                'policy_type' => $request->policyType,
                'policy_effective_date' => $request->policyEffectiveDate,
                'policy_expiration_date' => $request->policyExpirationDate,
            ]);

            $policyHolderAddress = Address::create([
                'street' => $request->policyHolder['address']['street'],
                'city' => $request->policyHolder['address']['city'],
                'state' => $request->policyHolder['address']['state'],
                'zip' => $request->policyHolder['address']['zip'],
            ]);

            $policyHolder = PolicyHolder::create([
                'policy_id' => $policy->id,
                'address_id' => $policyHolderAddress->id,
                'first_name' => $request->policyHolder['firstName'],
                'last_name' => $request->policyHolder['lastName'],
            ]);

            if ($request->has('drivers')) {
                foreach ($request->drivers as $driverData) {
                    Driver::create([
                        'policy_id' => $policy->id,
                        'first_name' => $driverData['firstName'],
                        'last_name' => $driverData['lastName'],
                        'age' => $driverData['age'],
                        'gender' => $driverData['gender'],
                        'marital_status' => $driverData['maritalStatus'],
                        'license_number' => $driverData['licenseNumber'],
                        'license_state' => $driverData['licenseState'],
                        'license_status' => $driverData['licenseStatus'],
                        'license_effective_date' => $driverData['licenseEffectiveDate'],
                        'license_expiration_date' => $driverData['licenseExpirationDate'],
                        'license_class' => $driverData['licenseClass'],
                    ]);
                }
            }

            if ($request->has('vehicles')) {
                foreach ($request->vehicles as $vehicleData) {
                    $vehicle = Vehicle::create([
                        'policy_id' => $policy->id,
                        'year' => $vehicleData['year'],
                        'make' => $vehicleData['make'],
                        'model' => $vehicleData['model'],
                        'vin' => $vehicleData['vin'],
                        'usage' => $vehicleData['usage'],
                        'primary_use' => $vehicleData['primaryUse'],
                        'annual_mileage' => $vehicleData['annualMileage'],
                        'ownership' => $vehicleData['ownership'],
                    ]);

                    $garagingAddress = Address::create([
                        'street' => $vehicleData['garagingAddress']['street'],
                        'city' => $vehicleData['garagingAddress']['city'],
                        'state' => $vehicleData['garagingAddress']['state'],
                        'zip' => $vehicleData['garagingAddress']['zip'],
                    ]);

                    GaragingAddress::create([
                        'vehicle_id' => $vehicle->id,
                        'address_id' => $garagingAddress->id,
                    ]);

                    if (isset($vehicleData['coverages'])) {
                        foreach ($vehicleData['coverages'] as $coverageData) {
                            Coverage::create([
                                'vehicle_id' => $vehicle->id,
                                'type' => $coverageData['type'],
                                'limit' => $coverageData['limit'],
                                'deductible' => $coverageData['deductible'],
                            ]);
                        }
                    }
                }
            }
            DB::commit();
            return response()->json(['message' => 'Policy created successfully', 'policy' => $policy], 201);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error("Error saving policy: " . $e->getMessage());
            return response()->json(['error' => 'Error saving policy'], 500);
        }
    }

    // Fetch a policy by ID
    public function show($id)
    {
        $policy = Policy::with([
            'policyHolder.address',
            'drivers',
            'vehicles.garagingAddress',
            'vehicles.coverages'
        ])->find($id);

        if (!$policy) {
            return response()->json(['message' => 'Policy not found'], 404);
        }

        return response()->json($policy);
    }

    public function update(Request $request, $id)
    {

        $request->validate([
            'policyNo' => 'required|string|unique:policies,policy_no,' . $id,
            'policyStatus' => 'required|string',
            'policyType' => 'required|string',
            'policyEffectiveDate' => 'required|date',
            'policyExpirationDate' => 'required|date',
            'policyHolder' => 'required|array',
            'policyHolder.firstName' => 'required|string',
            'policyHolder.lastName' => 'required|string',
            'policyHolder.address' => 'required|array',
            'policyHolder.address.street' => 'required|string',
            'policyHolder.address.city' => 'required|string',
            'policyHolder.address.state' => 'required|string',
            'policyHolder.address.zip' => 'required|string',
            'drivers' => 'nullable|array',
            'drivers.*.firstName' => 'required|string',
            'drivers.*.lastName' => 'required|string',
            'drivers.*.age' => 'required|integer',
            'drivers.*.gender' => 'required|string',
            'drivers.*.maritalStatus' => 'required|string',
            'drivers.*.licenseNumber' => 'required|string',
            'drivers.*.licenseState' => 'required|string',
            'drivers.*.licenseStatus' => 'required|string',
            'drivers.*.licenseEffectiveDate' => 'required|date',
            'drivers.*.licenseExpirationDate' => 'required|date',
            'drivers.*.licenseClass' => 'required|string',
            'vehicles' => 'nullable|array',
            'vehicles.*.year' => 'required|integer',
            'vehicles.*.make' => 'required|string',
            'vehicles.*.model' => 'required|string',
            'vehicles.*.vin' => 'required|string',
            'vehicles.*.usage' => 'required|string',
            'vehicles.*.primaryUse' => 'required|string',
            'vehicles.*.annualMileage' => 'required|integer',
            'vehicles.*.ownership' => 'required|string',
            'vehicles.*.garagingAddress' => 'required|array',
            'vehicles.*.garagingAddress.street' => 'required|string',
            'vehicles.*.garagingAddress.city' => 'required|string',
            'vehicles.*.garagingAddress.state' => 'required|string',
            'vehicles.*.garagingAddress.zip' => 'required|string',
            'vehicles.*.coverages' => 'nullable|array',
            'vehicles.*.coverages.*.type' => 'required|string',
            'vehicles.*.coverages.*.limit' => 'required|integer',
            'vehicles.*.coverages.*.deductible' => 'required|integer',
        ]);

        // Find the policy by ID
        $policy = Policy::findOrFail($id);

        $policy->update([
            'policy_no' => $request->policyNo,
            'policy_status' => $request->policyStatus,
            'policy_type' => $request->policyType,
            'policy_effective_date' => $request->policyEffectiveDate,
            'policy_expiration_date' => $request->policyExpirationDate,
        ]);

        $policy->policyHolder->update([
            'first_name' => $request->policyHolder['firstName'],
            'last_name' => $request->policyHolder['lastName'],
        ]);

        $policy->policyHolder->address->update([
            'street' => $request->policyHolder['address']['street'],
            'city' => $request->policyHolder['address']['city'],
            'state' => $request->policyHolder['address']['state'],
            'zip' => $request->policyHolder['address']['zip'],
        ]);

        if ($request->has('drivers')) {
            foreach ($request->drivers as $driverData) {
                $driver = Driver::updateOrCreate(
                    ['id' => $driverData['id'] ?? null],
                    [
                        'policy_id' => $policy->id,
                        'first_name' => $driverData['firstName'],
                        'last_name' => $driverData['lastName'],
                        'age' => $driverData['age'],
                        'gender' => $driverData['gender'],
                        'marital_status' => $driverData['maritalStatus'],
                        'license_number' => $driverData['licenseNumber'],
                        'license_state' => $driverData['licenseState'],
                        'license_status' => $driverData['licenseStatus'],
                        'license_effective_date' => $driverData['licenseEffectiveDate'],
                        'license_expiration_date' => $driverData['licenseExpirationDate'],
                        'license_class' => $driverData['licenseClass'],
                    ]
                );
            }
        }

        if ($request->has('vehicles')) {
            foreach ($request->vehicles as $vehicleData) {
                $vehicle = Vehicle::updateOrCreate(
                    ['id' => $vehicleData['id'] ?? null],
                    [
                        'policy_id' => $policy->id,
                        'year' => $vehicleData['year'],
                        'make' => $vehicleData['make'],
                        'model' => $vehicleData['model'],
                        'vin' => $vehicleData['vin'],
                        'usage' => $vehicleData['usage'],
                        'primary_use' => $vehicleData['primaryUse'],
                        'annual_mileage' => $vehicleData['annualMileage'],
                        'ownership' => $vehicleData['ownership'],
                    ]
                );


                if ($vehicle->garagingAddress) {
                    $vehicle->garagingAddress->update(
                        ['vehicle_id' => $vehicle->id],
                        [
                            'street' => $vehicleData['garagingAddress']['street'],
                            'city' => $vehicleData['garagingAddress']['city'],
                            'state' => $vehicleData['garagingAddress']['state'],
                            'zip' => $vehicleData['garagingAddress']['zip'],
                            'address_id' => $vehicle->garagingAddress->address_id ?? null, // Ensure this is passed
                        ]
                    );
                } else {
                    GaragingAddress::create([
                        'vehicle_id' => $vehicle->id,
                        'street' => $vehicleData['garagingAddress']['street'],
                        'city' => $vehicleData['garagingAddress']['city'],
                        'state' => $vehicleData['garagingAddress']['state'],
                        'zip' => $vehicleData['garagingAddress']['zip'],
                    ]);
                }

                if (isset($vehicleData['coverages'])) {
                    foreach ($vehicleData['coverages'] as $coverageData) {
                        Coverage::updateOrCreate(
                            ['id' => $coverageData['id'] ?? null],
                            [
                                'vehicle_id' => $vehicle->id,
                                'type' => $coverageData['type'],
                                'limit' => $coverageData['limit'],
                                'deductible' => $coverageData['deductible'],
                            ]
                        );
                    }
                }
            }
        }

        return response()->json(['message' => 'Policy updated successfully', 'policy' => $policy], 200);
    }


    // Delete a policy
    public function destroy($id)
    {
        $policy = Policy::find($id);

        if (!$policy) {
            return response()->json(['message' => 'Policy not found'], 404);
        }

        $policy->delete();

        return response()->json(['message' => 'Policy deleted successfully']);
    }

    // List all poliies
    public function index()
    {
        $policies = Policy::with([
            'policyHolder.address',
            'drivers',
            'vehicles.garagingAddress',
            'vehicles.coverages'
        ])->get();

        return response()->json($policies);
    }
}
