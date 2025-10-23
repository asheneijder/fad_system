<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\AccommodationClaim;
use App\Models\DailyAllowance;
use App\Models\TransportationClaim;
use App\Models\TravelClaim;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ClaimRequestController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = auth()->user();

        // Get claims for the authenticated user
        $travelClaims = TravelClaim::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($claim) {
                return [
                    'id' => $claim->id,
                    'type' => 'travel',
                    'date_of_travel' => $claim->date_of_travel,
                    'travel_from' => $claim->travel_from,
                    'travel_to' => $claim->travel_to,
                    'total_distance' => $claim->total_distance,
                    'total_cost' => $claim->total_cost,
                    'status' => $claim->status,
                    'purpose' => $claim->purpose,
                    'created_at' => $claim->created_at,
                ];
            });

        $dailyAllowances = DailyAllowance::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($allowance) {
                return [
                    'id' => $allowance->id,
                    'type' => 'daily',
                    'claim_date' => $allowance->claim_date,
                    'allowance_type' => $allowance->allowance_type,
                    'currency' => $allowance->currency,
                    'daily_rate' => $allowance->daily_rate,
                    'claim_percentage' => $allowance->claim_percentage,
                    'total_amount' => $allowance->claim_amount,
                    'status' => $allowance->status,
                    'purpose' => $allowance->purpose,
                    'destination' => $allowance->destination,
                    'created_at' => $allowance->created_at,
                ];
            });

        $accommodationClaims = AccommodationClaim::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($claim) {
                return [
                    'id' => $claim->id,
                    'type' => 'accommodation',
                    'check_in_date' => $claim->check_in_date,
                    'check_out_date' => $claim->check_out_date,
                    'hotel_name' => $claim->hotel_name,
                    'number_of_nights' => $claim->number_of_nights,
                    'total_amount' => $claim->total_amount,
                    'status' => $claim->status,
                    'purpose' => $claim->purpose,
                    'destination_city' => $claim->destination_city,
                    'created_at' => $claim->created_at,
                ];
            });

        $transportationClaims = TransportationClaim::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($claim) {
                return [
                    'id' => $claim->id,
                    'type' => 'transportation',
                    'claim_date' => $claim->claim_date,
                    'transport_type' => $claim->transport_type,
                    'purpose' => $claim->purpose,
                    'from_location' => $claim->from_location,
                    'to_location' => $claim->to_location,
                    'distance_km' => $claim->distance_km,
                    'total_amount' => $claim->amount,
                    'status' => $claim->status,
                    'created_at' => $claim->created_at,
                ];
            });

        // Calculate statistics
        $totalClaims = $travelClaims->count() + $dailyAllowances->count() + $accommodationClaims->count() + $transportationClaims->count();

        $allClaims = collect()
            ->merge($travelClaims)
            ->merge($dailyAllowances)
            ->merge($accommodationClaims)
            ->merge($transportationClaims);

        $submitted = $allClaims->where('status', 'submitted')->count();
        $approved = $allClaims->where('status', 'approved')->count();
        $rejected = $allClaims->where('status', 'rejected')->count();

        // Calculate total amount from all claim types
        $totalAmount = $travelClaims->sum('total_cost')
                     + $dailyAllowances->sum('total_amount')
                     + $accommodationClaims->sum('total_amount')
                     + $transportationClaims->sum('total_amount');

        $claimTypes = [
            [
                'id' => 'travel',
                'name' => 'Travel Claim',
                'description' => 'Claim for vehicle travel expenses',
                'icon' => 'pi pi-car',
                'color' => 'blue-500',
                'bgColor' => 'blue-100',
                'route' => route('user.travel-claims.create'),
            ],
            [
                'id' => 'daily',
                'name' => 'Daily Allowance',
                'description' => 'Claim for daily meal allowances',
                'icon' => 'pi pi-wallet',
                'color' => 'green-500',
                'bgColor' => 'green-100',
                'route' => route('user.daily-allowances.create'),
            ],
            [
                'id' => 'accommodation',
                'name' => 'Accommodation',
                'description' => 'Claim for hotel and lodging expenses',
                'icon' => 'pi pi-building',
                'color' => 'purple-500',
                'bgColor' => 'purple-100',
                'route' => route('user.accommodation-claims.create'),
            ],
            [
                'id' => 'transportation',
                'name' => 'Transportation',
                'description' => 'Claim for public transport expenses',
                'icon' => 'pi pi-map-marker',
                'color' => 'orange-500',
                'bgColor' => 'orange-100',
                'route' => route('user.transportation-claims.create'),
            ],
        ];

        return Inertia::render('User/ClaimRequest/Index', [
            'claimTypes' => $claimTypes,
            'travelClaims' => $travelClaims,
            'dailyAllowances' => $dailyAllowances,
            'accommodationClaims' => $accommodationClaims,
            'transportationClaims' => $transportationClaims,
            'statistics' => [
                'totalClaims' => $totalClaims,
                'submitted' => $submitted,
                'approved' => $approved,
                'rejected' => $rejected,
                'totalAmount' => $totalAmount,
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
