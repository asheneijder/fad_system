<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccommodationClaim;
use App\Models\DailyAllowance;
use App\Models\TransportationClaim;
use App\Models\TravelClaim;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ManageClaimRequestController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->query('search');
        $status = $request->query('status');
        $type = $request->query('type');
        $page = $request->query('page', 1);
        $perPage = $request->query('per_page', 10);

        $user = Auth::user();

        // Get all claim types with their counts
        $claimTypes = $this->getClaimTypesWithCounts($user);

        // Get unified claims query
        $claims = $this->getUnifiedClaimsQuery($user, $search, $status, $type, $perPage, $page);

        // Statistics
        $statistics = $this->getStatistics($user);

        // Status options for filter
        $statusOptions = [
            ['label' => 'All Status', 'value' => 'all'],
            ['label' => 'Draft', 'value' => 'draft'],
            ['label' => 'Pending Approval', 'value' => 'submitted'],
            ['label' => 'Pending', 'value' => 'pending'],
            ['label' => 'Approved', 'value' => 'approved'],
            ['label' => 'Rejected', 'value' => 'rejected'],
            ['label' => 'Paid', 'value' => 'paid'],
        ];

        // Claim type options
        $typeOptions = [
            ['label' => 'All Types', 'value' => 'all'],
            ['label' => 'Travel Claim', 'value' => 'travel'],
            ['label' => 'Daily Allowance', 'value' => 'daily'],
            ['label' => 'Accommodation', 'value' => 'accommodation'],
            ['label' => 'Transportation', 'value' => 'transportation'],
        ];

        return Inertia::render('Admin/ManageClaimRequest/Index', [
            'claims' => $claims,
            'filters' => $request->only(['search', 'status', 'type']),
            'statistics' => $statistics,
            'claimTypes' => $claimTypes,
            'statusOptions' => $statusOptions,
            'typeOptions' => $typeOptions,
            'userRole' => $user->hasRole('system-admin') ? 'system-admin' : 'approver',
        ]);
    }

    /**
     * Get unified claims query across all claim types
     */
    private function getUnifiedClaimsQuery($user, $search, $status, $type, $perPage, $page)
    {
        // Get claims from all types
        $travelClaims = $this->getTravelClaims($user, $search, $status, $type);
        $dailyClaims = $this->getDailyClaims($user, $search, $status, $type);
        $accommodationClaims = $this->getAccommodationClaims($user, $search, $status, $type);
        $transportationClaims = $this->getTransportationClaims($user, $search, $status, $type);

        // Combine all claims
        $allClaims = $travelClaims->concat($dailyClaims)
            ->concat($accommodationClaims)
            ->concat($transportationClaims);

        // Sort by created date
        $allClaims = $allClaims->sortByDesc('created_at');

        // Manual pagination
        $total = $allClaims->count();
        $offset = ($page - 1) * $perPage;
        $paginatedClaims = $allClaims->slice($offset, $perPage);

        return new \Illuminate\Pagination\LengthAwarePaginator(
            $paginatedClaims->values(),
            $total,
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }

    /**
     * Get travel claims
     */
    private function getTravelClaims($user, $search, $status, $type)
    {
        if ($type && $type !== 'all' && $type !== 'travel') {
            return collect();
        }

        $query = TravelClaim::with(['user', 'approver']);

        if (! $user->hasRole('system-admin')) {
            $query->where('approver_id', $user->id);
        }

        $query->when($search, function ($q, $search) {
            $q->where(function ($query) use ($search) {
                $query->where('purpose', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        })
            ->when($status && $status !== 'all', function ($q) use ($status) {
                $q->where('status', $status);
            });

        return $query->get()->map(function ($claim) {
            return $this->transformTravelClaim($claim);
        });
    }

    /**
     * Get daily allowance claims
     */
    private function getDailyClaims($user, $search, $status, $type)
    {
        if ($type && $type !== 'all' && $type !== 'daily') {
            return collect();
        }

        $query = DailyAllowance::with(['user', 'approver']);

        if (! $user->hasRole('system-admin')) {
            $query->where('approver_id', $user->id);
        }

        $query->when($search, function ($q, $search) {
            $q->where(function ($query) use ($search) {
                $query->where('purpose', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        })
            ->when($status && $status !== 'all', function ($q) use ($status) {
                $q->where('status', $status);
            });

        return $query->get()->map(function ($claim) {
            return $this->transformDailyClaim($claim);
        });
    }

    /**
     * Get accommodation claims
     */
    private function getAccommodationClaims($user, $search, $status, $type)
    {
        if ($type && $type !== 'all' && $type !== 'accommodation') {
            return collect();
        }

        $query = AccommodationClaim::with(['user', 'approver']);

        if (! $user->hasRole('system-admin')) {
            $query->where('approver_id', $user->id);
        }

        $query->when($search, function ($q, $search) {
            $q->where(function ($query) use ($search) {
                $query->where('purpose', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        })
            ->when($status && $status !== 'all', function ($q) use ($status) {
                $q->where('status', $status);
            });

        return $query->get()->map(function ($claim) {
            return $this->transformAccommodationClaim($claim);
        });
    }

    /**
     * Get transportation claims
     */
    private function getTransportationClaims($user, $search, $status, $type)
    {
        if ($type && $type !== 'all' && $type !== 'transportation') {
            return collect();
        }

        $query = TransportationClaim::with(['user', 'approver']);

        if (! $user->hasRole('system-admin')) {
            $query->where('approver_id', $user->id);
        }

        $query->when($search, function ($q, $search) {
            $q->where(function ($query) use ($search) {
                $query->where('purpose', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        })
            ->when($status && $status !== 'all', function ($q) use ($status) {
                $q->where('status', $status);
            });

        return $query->get()->map(function ($claim) {
            return $this->transformTransportationClaim($claim);
        });
    }

    /**
     * Transform travel claim for unified display
     */
    private function transformTravelClaim($claim)
    {
        return [
            'id' => $claim->id,
            'type' => 'travel',
            'type_display' => 'Travel Claim',
            'user' => $claim->user,
            'approver' => $claim->approver,
            'status' => $claim->status,
            'purpose' => $claim->purpose,
            'amount' => $claim->total_cost,
            'submitted_at' => $claim->submitted_at,
            'created_at' => $claim->created_at,
            'details' => [
                'vehicle_type' => $claim->vehicle_type,
                'distance' => $claim->total_distance.' km',
                'rate' => 'RM '.$claim->rate_per_km.'/km',
            ],
            'model_type' => TravelClaim::class,
        ];
    }

    /**
     * Transform daily allowance claim for unified display
     */
    private function transformDailyClaim($claim)
    {
        return [
            'id' => $claim->id,
            'type' => 'daily',
            'type_display' => 'Daily Allowance',
            'user' => $claim->user,
            'approver' => $claim->approver,
            'status' => $claim->status,
            'purpose' => $claim->purpose,
            'amount' => $claim->claim_amount,
            'submitted_at' => $claim->submitted_at,
            'created_at' => $claim->created_at,
            'details' => [
                'date' => $claim->claim_date,
                'type' => $claim->allowance_type,
                'rate' => 'RM '.$claim->daily_rate,
                'percentage' => $claim->claim_percentage.'%',
            ],
            'model_type' => DailyAllowance::class,
        ];
    }

    /**
     * Transform accommodation claim for unified display
     */
    private function transformAccommodationClaim($claim)
    {
        return [
            'id' => $claim->id,
            'type' => 'accommodation',
            'type_display' => 'Accommodation Claim',
            'user' => $claim->user,
            'approver' => $claim->approver,
            'status' => $claim->status,
            'purpose' => $claim->purpose,
            'amount' => $claim->total_amount,
            'submitted_at' => $claim->submitted_at,
            'created_at' => $claim->created_at,
            'details' => [
                'hotel' => $claim->hotel_name,
                'nights' => $claim->number_of_nights.' nights',
                'rate' => 'RM '.$claim->rate_per_night.'/night',
            ],
            'model_type' => AccommodationClaim::class,
        ];
    }

    /**
     * Transform transportation claim for unified display
     */
    private function transformTransportationClaim($claim)
    {
        return [
            'id' => $claim->id,
            'type' => 'transportation',
            'type_display' => 'Transportation Claim',
            'user' => $claim->user,
            'approver' => $claim->approver,
            'status' => $claim->status,
            'purpose' => $claim->purpose,
            'amount' => $claim->amount,
            'submitted_at' => $claim->submitted_at,
            'created_at' => $claim->created_at,
            'details' => [
                'transport_type' => $claim->transport_type_display,
                'route' => $claim->from_location.' → '.$claim->to_location,
                'trips' => $claim->number_of_trips.' trips',
            ],
            'model_type' => TransportationClaim::class,
        ];
    }

    /**
     * Get claim types with their counts
     */
    private function getClaimTypesWithCounts($user)
    {
        $travelQuery = TravelClaim::query();
        $dailyQuery = DailyAllowance::query();
        $accommodationQuery = AccommodationClaim::query();
        $transportationQuery = TransportationClaim::query();

        if (! $user->hasRole('system-admin')) {
            $travelQuery->where('approver_id', $user->id);
            $dailyQuery->where('approver_id', $user->id);
            $accommodationQuery->where('approver_id', $user->id);
            $transportationQuery->where('approver_id', $user->id);
        }

        return [
            [
                'id' => 'travel',
                'name' => 'Travel Claim',
                'description' => 'Claim for travel expenses including fuel, tolls, and transportation',
                'icon' => 'pi pi-car',
                'color' => 'blue-500',
                'bgColor' => 'blue-100',
                'total' => $travelQuery->count(),
                'pending' => $travelQuery->clone()->where('status', 'submitted')->orWhere('status', 'pending')->count(),
            ],
            [
                'id' => 'daily',
                'name' => 'Daily Allowance',
                'description' => 'Claim for daily meal allowances',
                'icon' => 'pi pi-wallet',
                'color' => 'green-500',
                'bgColor' => 'green-100',
                'total' => $dailyQuery->count(),
                'pending' => $dailyQuery->clone()->where('status', 'submitted')->orWhere('status', 'pending')->count(),
            ],
            [
                'id' => 'accommodation',
                'name' => 'Accommodation Claim',
                'description' => 'Claim for hotel accommodation',
                'icon' => 'pi pi-building',
                'color' => 'purple-500',
                'bgColor' => 'purple-100',
                'total' => $accommodationQuery->count(),
                'pending' => $accommodationQuery->clone()->where('status', 'submitted')->orWhere('status', 'pending')->count(),
            ],
            [
                'id' => 'transportation',
                'name' => 'Transportation Claim',
                'description' => 'Claim for Grab, taxi, MRT, bus, etc.',
                'icon' => 'pi pi-map-marker',
                'color' => 'orange-500',
                'bgColor' => 'orange-100',
                'total' => $transportationQuery->count(),
                'pending' => $transportationQuery->clone()->where('status', 'submitted')->orWhere('status', 'pending')->count(),
            ],
        ];
    }

    /**
     * Get statistics
     */
    private function getStatistics($user)
    {
        $travelQuery = TravelClaim::query();
        $dailyQuery = DailyAllowance::query();
        $accommodationQuery = AccommodationClaim::query();
        $transportationQuery = TransportationClaim::query();

        if (! $user->hasRole('system-admin')) {
            $travelQuery->where('approver_id', $user->id);
            $dailyQuery->where('approver_id', $user->id);
            $accommodationQuery->where('approver_id', $user->id);
            $transportationQuery->where('approver_id', $user->id);
        }

        $total = $travelQuery->count() + $dailyQuery->count() + $accommodationQuery->count() + $transportationQuery->count();
        $pending = $travelQuery->clone()->whereIn('status', ['submitted', 'pending'])->count()
            + $dailyQuery->clone()->whereIn('status', ['submitted', 'pending'])->count()
            + $accommodationQuery->clone()->whereIn('status', ['submitted', 'pending'])->count()
            + $transportationQuery->clone()->whereIn('status', ['submitted', 'pending'])->count();
        $approved = $travelQuery->clone()->where('status', 'approved')->count()
            + $dailyQuery->clone()->where('status', 'approved')->count()
            + $accommodationQuery->clone()->where('status', 'approved')->count()
            + $transportationQuery->clone()->where('status', 'approved')->count();
        $rejected = $travelQuery->clone()->where('status', 'rejected')->count()
            + $dailyQuery->clone()->where('status', 'rejected')->count()
            + $accommodationQuery->clone()->where('status', 'rejected')->count()
            + $transportationQuery->clone()->where('status', 'rejected')->count();
        $draft = $travelQuery->clone()->where('status', 'draft')->count()
            + $dailyQuery->clone()->where('status', 'draft')->count()
            + $accommodationQuery->clone()->where('status', 'draft')->count()
            + $transportationQuery->clone()->where('status', 'draft')->count();

        return [
            'total' => $total,
            'pending' => $pending,
            'approved' => $approved,
            'rejected' => $rejected,
            'draft' => $draft,
        ];
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        abort(404);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        abort(404);
    }

    /**
     * Display the specified resource - Keep for backward compatibility
     */
    public function show($id)
    {
        $user = Auth::user();

        // Try to find the claim in each model and redirect to type-specific page
        $claim = TravelClaim::find($id);
        if ($claim) {
            return $this->showTravel($id);
        }

        $claim = DailyAllowance::find($id);
        if ($claim) {
            return $this->showDaily($id);
        }

        $claim = AccommodationClaim::find($id);
        if ($claim) {
            return $this->showAccommodation($id);
        }

        $claim = TransportationClaim::find($id);
        if ($claim) {
            return $this->showTransportation($id);
        }

        abort(404, 'Claim not found.');
    }

    /**
     * Show travel claim details
     */
    public function showTravel($id)
    {
        $user = Auth::user();
        $claim = TravelClaim::with(['user', 'approver', 'media'])->find($id);

        if (! $claim) {
            abort(404, 'Travel claim not found.');
        }

        // Authorization
        if (! $user->hasRole('system-admin') && $claim->approver_id !== $user->id) {
            abort(403, 'You are not authorized to view this claim.');
        }

        $claimData = $this->transformTravelClaimForShow($claim);

        return Inertia::render('Admin/ManageClaimRequest/TravelShow', [
            'claim' => $claimData,
            'userRole' => $user->hasRole('system-admin') ? 'system-admin' : 'approver',
        ]);
    }

    /**
     * Show daily allowance claim details
     */
    public function showDaily($id)
    {
        $user = Auth::user();
        $claim = DailyAllowance::with(['user', 'approver', 'media'])->find($id);

        if (! $claim) {
            abort(404, 'Daily allowance claim not found.');
        }

        // Authorization
        if (! $user->hasRole('system-admin') && $claim->approver_id !== $user->id) {
            abort(403, 'You are not authorized to view this claim.');
        }

        $claimData = $this->transformDailyClaimForShow($claim);

        return Inertia::render('Admin/ManageClaimRequest/DailyShow', [
            'claim' => $claimData,
            'userRole' => $user->hasRole('system-admin') ? 'system-admin' : 'approver',
        ]);
    }

    /**
     * Show accommodation claim details
     */
    public function showAccommodation($id)
    {
        $user = Auth::user();
        $claim = AccommodationClaim::with(['user', 'approver', 'media'])->find($id);

        if (! $claim) {
            abort(404, 'Accommodation claim not found.');
        }

        // Authorization
        if (! $user->hasRole('system-admin') && $claim->approver_id !== $user->id) {
            abort(403, 'You are not authorized to view this claim.');
        }

        $claimData = $this->transformAccommodationClaimForShow($claim);

        return Inertia::render('Admin/ManageClaimRequest/AccommodationShow', [
            'claim' => $claimData,
            'userRole' => $user->hasRole('system-admin') ? 'system-admin' : 'approver',
        ]);
    }

    /**
     * Show transportation claim details
     */
    public function showTransportation($id)
    {
        $user = Auth::user();
        $claim = TransportationClaim::with(['user', 'approver', 'media'])->find($id);

        if (! $claim) {
            abort(404, 'Transportation claim not found.');
        }

        // Authorization
        if (! $user->hasRole('system-admin') && $claim->approver_id !== $user->id) {
            abort(403, 'You are not authorized to view this claim.');
        }

        $claimData = $this->transformTransportationClaimForShow($claim);

        return Inertia::render('Admin/ManageClaimRequest/TransportationShow', [
            'claim' => $claimData,
            'userRole' => $user->hasRole('system-admin') ? 'system-admin' : 'approver',
        ]);
    }

    /**
     * Transform travel claim for show page
     */
    private function transformTravelClaimForShow($claim)
    {
        $documents = [];

        if ($claim->media && $claim->media->count() > 0) {
            $documents = $claim->media->map(function ($media) {
                return [
                    'name' => $media->file_name,
                    'url' => $media->getUrl(),
                    'size' => $media->size,
                    'mime_type' => $media->mime_type,
                ];
            })->toArray();
        }

        return [
            'id' => $claim->id,
            'user_id' => $claim->user_id,
            'type' => 'travel',
            'type_display' => 'Travel Claim',
            'user' => $claim->user,
            'approver' => $claim->approver,
            'status' => $claim->status,

            // Vehicle information
            'vehicle_type' => $claim->vehicle_type,
            'registration_plate_number' => $claim->registration_plate_number,
            'cubic_capacity' => $claim->cubic_capacity,

            // Travel dates
            'date_of_travel' => $claim->date_of_travel,
            'end_date_of_travel' => $claim->end_date_of_travel,
            'is_multiple_days' => $claim->is_multiple_days,
            'claim_date' => $claim->claim_date,

            // Travel locations
            'travel_from' => $claim->travel_from,
            'travel_to' => $claim->travel_to,
            'purpose' => $claim->purpose,

            // Distance and cost
            'total_distance' => $claim->total_distance,
            'rate_per_km' => $claim->rate_per_km,
            'total_cost' => $claim->total_cost,

            // Approval information
            'approver_id' => $claim->approver_id,
            'approval_date' => $claim->approval_date,
            'rejection_reason' => $claim->rejection_reason,

            // Timestamps
            'created_at' => $claim->created_at,
            'updated_at' => $claim->updated_at,
            'submitted_at' => $claim->submitted_at,
            'approved_at' => $claim->approved_at,
            'rejected_at' => $claim->rejected_at,

            // Travel legs data (from the array cast)
            'travel_legs_data' => $claim->travel_legs_data,
            'calculated_total_distance' => $claim->calculateTotalDistanceFromLegs(),

            // Documents
            'documents' => $documents,
        ];
    }

    /**
     * Transform daily allowance claim for show page
     */
    private function transformDailyClaimForShow($claim)
    {
        $documents = [];

        if ($claim->media && $claim->media->count() > 0) {
            $documents = $claim->media->map(function ($media) {
                return [
                    'name' => $media->file_name,
                    'url' => $media->getUrl(),
                    'size' => $media->size,
                    'mime_type' => $media->mime_type,
                ];
            })->toArray();
        }

        return [
            'id' => $claim->id,
            'user_id' => $claim->user_id,
            'type' => 'daily',
            'type_display' => 'Daily Allowance',
            'user' => $claim->user,
            'approver' => $claim->approver,
            'status' => $claim->status,

            // Date fields
            'claim_date' => $claim->claim_date,
            'created_at' => $claim->created_at,
            'updated_at' => $claim->updated_at,
            'submitted_at' => $claim->submitted_at,
            'approved_at' => $claim->approved_at,
            'rejected_at' => $claim->rejected_at,
            'approval_date' => $claim->approval_date,

            // Allowance details
            'allowance_type' => $claim->allowance_type,
            'currency' => $claim->currency,
            'daily_rate' => $claim->daily_rate,
            'claim_percentage' => $claim->claim_percentage,
            'claim_amount' => $claim->claim_amount,

            // Additional information
            'purpose' => $claim->purpose,
            'destination' => $claim->destination,
            'rejection_reason' => $claim->rejection_reason,
            'approver_id' => $claim->approver_id,

            // Documents
            'documents' => $documents,
        ];
    }

    /**
     * Transform accommodation claim for show page
     */
    private function transformAccommodationClaimForShow($claim)
    {
        $documents = [];

        if ($claim->media && $claim->media->count() > 0) {
            $documents = $claim->media->map(function ($media) {
                return [
                    'name' => $media->file_name,
                    'url' => $media->getUrl(),
                    'size' => $media->size,
                    'mime_type' => $media->mime_type,
                ];
            })->toArray();
        }

        return [
            'id' => $claim->id,
            'type' => 'accommodation',
            'type_display' => 'Accommodation Claim',
            'user' => $claim->user,
            'approver' => $claim->approver,
            'status' => $claim->status,
            'purpose' => $claim->purpose,
            'amount' => $claim->total_amount,
            'updated_at' => $claim->updated_at,
            'created_at' => $claim->created_at,
            'approved_at' => $claim->approved_at,
            'rejected_at' => $claim->rejected_at,
            'rejection_reason' => $claim->rejection_reason,

            // Accommodation specific fields
            'hotel_name' => $claim->hotel_name,
            'check_in_date' => $claim->check_in_date,
            'check_out_date' => $claim->check_out_date,
            'number_of_nights' => $claim->number_of_nights,
            'rate_per_night' => $claim->rate_per_night,
            'hotel_address' => $claim->hotel_address,
            'hotel_city' => $claim->hotel_city,
            'hotel_country' => $claim->hotel_country,

            // New additional fields
            'currency' => $claim->currency,
            'tax_percentage' => $claim->tax_percentage,
            'service_charge_percentage' => $claim->service_charge_percentage,
            'tax_amount' => $claim->tax_amount,
            'service_charge_amount' => $claim->service_charge_amount,
            'subtotal_amount' => $claim->subtotal_amount,
            'destination_city' => $claim->destination_city,
            'destination_country' => $claim->destination_country,

            // Additional data
            'documents' => $documents,
        ];
    }

    /**
     * Transform transportation claim for show page
     */
    private function transformTransportationClaimForShow($claim)
    {
        $documents = [];

        if ($claim->media && $claim->media->count() > 0) {
            $documents = $claim->media->map(function ($media) {
                return [
                    'name' => $media->file_name,
                    'url' => $media->getUrl(),
                    'size' => $media->size,
                    'mime_type' => $media->mime_type,
                ];
            })->toArray();
        }

        return [
            'id' => $claim->id,
            'user_id' => $claim->user_id,
            'type' => 'transportation',
            'type_display' => 'Transportation Claim',
            'user' => $claim->user,
            'approver' => $claim->approver,
            'status' => $claim->status,

            // Date fields
            'claim_date' => $claim->claim_date,
            'travel_date' => $claim->travel_date,
            'updated_at' => $claim->updated_at,
            'created_at' => $claim->created_at,
            'approved_at' => $claim->approved_at,
            'rejected_at' => $claim->rejected_at,
            'approval_date' => $claim->approval_date,

            // Transportation details
            'transport_type' => $claim->transport_type,
            'transport_type_display' => $claim->transport_type_display,
            'purpose' => $claim->purpose,
            'from_location' => $claim->from_location,
            'to_location' => $claim->to_location,
            'distance_km' => $claim->distance_km,
            'rate_per_km' => $claim->rate_per_km,
            'trip_type' => $claim->trip_type,
            'number_of_trips' => $claim->number_of_trips,
            'cost_per_trip' => $claim->cost_per_trip,

            // Financial details
            'amount' => $claim->amount,
            'currency' => $claim->currency,

            // Additional information
            'receipt_number' => $claim->receipt_number,
            'remarks' => $claim->remarks,
            'rejection_reason' => $claim->rejection_reason,
            'approver_id' => $claim->approver_id,

            // Documents
            'documents' => $documents,
        ];
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        abort(404);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();

        // Try to find the claim in each model
        $claim = TravelClaim::find($id);
        $modelType = TravelClaim::class;

        if (! $claim) {
            $claim = DailyAllowance::find($id);
            $modelType = DailyAllowance::class;
        }

        if (! $claim) {
            $claim = AccommodationClaim::find($id);
            $modelType = AccommodationClaim::class;
        }

        if (! $claim) {
            $claim = TransportationClaim::find($id);
            $modelType = TransportationClaim::class;
        }

        if (! $claim) {
            abort(404, 'Claim not found.');
        }

        // Authorization
        if (! $user->hasRole('system-admin') && $claim->approver_id !== $user->id) {
            abort(403, 'You are not authorized to process this claim.');
        }

        $validated = $request->validate([
            'action' => 'required|in:approve,reject',
            'notes' => 'nullable|string|max:500',
        ]);

        $updateData = [
            'approver_id' => $user->id,
        ];

        if ($validated['action'] === 'approve') {
            $updateData['status'] = 'approved';
            $updateData['approved_at'] = now();
            $message = 'Claim approved successfully';
        } else {
            $request->validate([
                'notes' => 'required|string|max:500',
            ]);

            $updateData['status'] = 'rejected';
            $updateData['rejection_reason'] = $validated['notes'];
            $updateData['rejected_at'] = now();
            $message = 'Claim rejected successfully';
        }

        $claim->update($updateData);

        return redirect()->route('manage.claim-request.index')
            ->with('success', $message);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        abort(404);
    }

    /**
     * Bulk actions for claims
     */
    public function bulkAction(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'action' => 'required|in:approve,reject',
            'claim_ids' => 'required|array|min:1',
            'notes' => 'nullable|string|max:500',
        ]);

        $processedCount = 0;
        $errors = [];

        foreach ($validated['claim_ids'] as $claimId) {
            // Try to find claim in each model
            $claim = TravelClaim::find($claimId);
            if (! $claim) {
                $claim = DailyAllowance::find($claimId);
            }
            if (! $claim) {
                $claim = AccommodationClaim::find($claimId);
            }
            if (! $claim) {
                $claim = TransportationClaim::find($claimId);
            }

            if (! $claim) {
                $errors[] = "Claim ID {$claimId} not found.";

                continue;
            }

            // Authorization check
            if (! $user->hasRole('system-admin') && $claim->approver_id !== $user->id) {
                $errors[] = "You are not authorized to process claim ID {$claimId}.";

                continue;
            }

            $updateData = [
                'approver_id' => $user->id,
            ];

            if ($validated['action'] === 'approve') {
                $updateData['status'] = 'approved';
                $updateData['approved_at'] = now();
            } else {
                if (empty($validated['notes'])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Rejection notes are required.',
                    ], 422);
                }
                $updateData['status'] = 'rejected';
                $updateData['rejection_reason'] = $validated['notes'];
                $updateData['rejected_at'] = now();
            }

            $claim->update($updateData);
            $processedCount++;
        }

        if ($processedCount === 0) {
            return response()->json([
                'success' => false,
                'message' => 'No claims were processed. '.implode(' ', $errors),
            ], 422);
        }

        $message = $validated['action'] === 'approve'
            ? "{$processedCount} claim(s) approved successfully"
            : "{$processedCount} claim(s) rejected successfully";

        if (! empty($errors)) {
            $message .= '. Some errors occurred: '.implode(' ', array_slice($errors, 0, 3));
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'processed_count' => $processedCount,
            'error_count' => count($errors),
        ]);
    }
}
