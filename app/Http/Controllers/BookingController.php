<?php

namespace App\Http\Controllers;

use App\BookingType;
use App\DurationType;
use App\Models\Space;
use App\Services\BookingValidationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class BookingController extends Controller
{
    public function __construct(
        protected BookingValidationService $validationService
    ) {}

    /**
     * Show the booking form
     */
    public function create(Request $request, string $locale = 'en')
    {
        // Validate and set locale
        if (! in_array($locale, ['en', 'nl'])) {
            $locale = 'en';
        }

        App::setLocale($locale);

        // Get all active spaces grouped by type
        $spaces = Space::active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->groupBy(fn ($space) => $space->type->value);

        return view('booking.create', [
            'spaces' => $spaces,
            'bookingTypes' => BookingType::cases(),
            'durationTypes' => DurationType::cases(),
        ]);
    }

    /**
     * Check availability for a space
     */
    public function checkAvailability(Request $request)
    {
        $validated = $request->validate([
            'space_id' => 'required|exists:spaces,id',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after:start_date',
        ]);

        $space = Space::findOrFail($validated['space_id']);
        $startDate = \Carbon\Carbon::parse($validated['start_date']);
        $endDate = \Carbon\Carbon::parse($validated['end_date']);

        $isAvailable = $this->validationService->isSpaceAvailable(
            $space,
            $startDate,
            $endDate
        );

        return response()->json([
            'available' => $isAvailable,
            'space' => $space->name,
        ]);
    }

    /**
     * Get pricing for a booking
     */
    public function getPricing(Request $request)
    {
        $validated = $request->validate([
            'space_id' => 'required|exists:spaces,id',
            'booking_type' => 'required|string',
            'duration_type' => 'required|string',
            'people_count' => 'nullable|integer|min:1',
        ]);

        $space = Space::findOrFail($validated['space_id']);

        // Find matching pricing rule
        $pricingRule = $space->pricingRules()
            ->active()
            ->where('booking_type', BookingType::from($validated['booking_type']))
            ->where('duration_type', DurationType::from($validated['duration_type']))
            ->validOn(now())
            ->first();

        if (! $pricingRule) {
            return response()->json([
                'error' => 'No pricing found for this combination',
            ], 404);
        }

        return response()->json([
            'price' => $pricingRule->price,
            'discounted_price' => $pricingRule->getDiscountedPrice(),
            'discount_percentage' => $pricingRule->discount_percentage,
        ]);
    }
}
