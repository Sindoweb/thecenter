<?php

namespace App\Services;

use App\BookingStatus;
use App\Models\Booking;
use App\Models\Space;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class BookingValidationService
{
    /**
     * Check if a space is available for the given date range
     */
    public function isSpaceAvailable(
        Space $space,
        Carbon $startDate,
        Carbon $endDate,
        ?int $excludeBookingId = null
    ): bool {
        $conflicts = $this->getConflictingBookings(
            collect([$space]),
            $startDate,
            $endDate,
            $excludeBookingId
        );

        if ($conflicts->isNotEmpty()) {
            Log::info("Space {$space->name} is not available", [
                'space_id' => $space->id,
                'start' => $startDate,
                'end' => $endDate,
                'conflicts' => $conflicts->count(),
            ]);

            return false;
        }

        return true;
    }

    /**
     * Check if multiple spaces are available
     *
     * @param  Collection<int, Space>  $spaces
     */
    public function areSpacesAvailable(
        Collection $spaces,
        Carbon $startDate,
        Carbon $endDate,
        ?int $excludeBookingId = null
    ): bool {
        $conflicts = $this->getConflictingBookings(
            $spaces,
            $startDate,
            $endDate,
            $excludeBookingId
        );

        return $conflicts->isEmpty();
    }

    /**
     * Get all conflicting bookings for the given spaces and date range
     * This includes bookings for:
     * 1. The requested spaces directly
     * 2. Combined spaces that include the requested spaces
     * 3. Spaces that the requested spaces can combine with
     *
     * @param  Collection<int, Space>  $spaces
     * @return Collection<int, Booking>
     */
    public function getConflictingBookings(
        Collection $spaces,
        Carbon $startDate,
        Carbon $endDate,
        ?int $excludeBookingId = null
    ): Collection {
        $spaceIds = $spaces->pluck('id')->toArray();

        // Get all spaces that could conflict with our booking
        $conflictingSpaceIds = $this->getConflictingSpaceIds($spaces);

        // Find any confirmed/pending bookings that overlap with our date range
        // and involve any of the potentially conflicting spaces
        $query = Booking::query()
            ->whereIn('status', [BookingStatus::Pending, BookingStatus::Confirmed])
            ->whereHas('spaces', function ($query) use ($conflictingSpaceIds): void {
                $query->whereIn('spaces.id', $conflictingSpaceIds);
            })
            ->where(function ($query) use ($startDate, $endDate): void {
                // Check for any overlap:
                // Booking starts before our end date AND ends after our start date
                $query->where('start_date', '<', $endDate)
                    ->where('end_date', '>', $startDate);
            });

        // Exclude current booking if we're updating
        if ($excludeBookingId) {
            $query->where('id', '!=', $excludeBookingId);
        }

        $conflicts = $query->with('spaces')->get();

        Log::debug('Checking booking conflicts', [
            'requested_spaces' => $spaceIds,
            'conflicting_space_ids' => $conflictingSpaceIds,
            'start' => $startDate->toDateTimeString(),
            'end' => $endDate->toDateTimeString(),
            'conflicts_found' => $conflicts->count(),
        ]);

        return $conflicts;
    }

    /**
     * Get all space IDs that could conflict with the given spaces
     * This includes:
     * 1. The spaces themselves
     * 2. Any combined spaces that include these spaces
     * 3. Any spaces these can combine with
     *
     * @param  Collection<int, Space>  $spaces
     */
    protected function getConflictingSpaceIds(Collection $spaces): array
    {
        $spaceIds = $spaces->pluck('id')->toArray();
        $conflictingIds = $spaceIds;

        foreach ($spaces as $space) {
            // Add spaces this space can combine with
            if ($space->can_combine_with && is_array($space->can_combine_with)) {
                $conflictingIds = array_merge($conflictingIds, $space->can_combine_with);
            }

            // Find any combined spaces that include this space
            $combinedSpaces = Space::query()
                ->whereJsonContains('can_combine_with', $space->id)
                ->pluck('id')
                ->toArray();

            $conflictingIds = array_merge($conflictingIds, $combinedSpaces);
        }

        return array_unique($conflictingIds);
    }

    /**
     * Validate a booking and return validation errors
     *
     * @param  Collection<int, Space>  $spaces
     */
    public function validateBooking(
        Collection $spaces,
        Carbon $startDate,
        Carbon $endDate,
        int $numberOfPeople,
        ?int $excludeBookingId = null
    ): array {
        $errors = [];

        // Validate date range
        if ($endDate->lte($startDate)) {
            $errors[] = 'End date must be after start date';
        }

        // Validate dates are in the future (for new bookings)
        if (! $excludeBookingId && $startDate->lt(now())) {
            $errors[] = 'Start date must be in the future';
        }

        // Validate capacity
        $totalCapacity = $spaces->sum('capacity');
        if ($numberOfPeople > $totalCapacity) {
            $errors[] = "Number of people ({$numberOfPeople}) exceeds total capacity ({$totalCapacity})";
        }

        // Check for conflicts
        if (! $this->areSpacesAvailable($spaces, $startDate, $endDate, $excludeBookingId)) {
            $conflicts = $this->getConflictingBookings($spaces, $startDate, $endDate, $excludeBookingId);
            $conflictingSpaceNames = $conflicts
                ->pluck('spaces')
                ->flatten()
                ->pluck('name')
                ->unique()
                ->implode(', ');

            $errors[] = "Selected spaces are not available during this time. Conflicts with: {$conflictingSpaceNames}";
        }

        return $errors;
    }

    /**
     * Get available capacity for a space during a date range
     * Useful for co-working overflow logic
     */
    public function getAvailableCapacity(Space $space, Carbon $startDate, Carbon $endDate): int
    {
        if (! $this->isSpaceAvailable($space, $startDate, $endDate)) {
            return 0;
        }

        return $space->capacity;
    }

    /**
     * Get all available spaces for a date range
     *
     * @return Collection<int, Space>
     */
    public function getAvailableSpaces(Carbon $startDate, Carbon $endDate): Collection
    {
        $allSpaces = Space::active()->get();

        return $allSpaces->filter(function (Space $space) use ($startDate, $endDate): bool {
            return $this->isSpaceAvailable($space, $startDate, $endDate);
        });
    }

    /**
     * Calculate total available co-working capacity including overflow
     * When conference rooms are available, they can be used for co-working
     */
    public function getCoWorkingCapacity(Carbon $startDate, Carbon $endDate): int
    {
        $capacity = 0;

        // Base co-working area
        $coWorkingSpace = Space::where('slug', 'co-working-area')->first();
        if ($coWorkingSpace && $this->isSpaceAvailable($coWorkingSpace, $startDate, $endDate)) {
            $capacity += $coWorkingSpace->capacity;
        }

        // Add conference rooms if available (overflow)
        $conferenceRooms = Space::whereIn('slug', ['the-glow', 'the-ray'])->get();
        foreach ($conferenceRooms as $room) {
            if ($this->isSpaceAvailable($room, $startDate, $endDate)) {
                $capacity += $room->capacity;
            }
        }

        return $capacity;
    }

    /**
     * Check if a combined space booking is valid
     * For example, "The Universe" requires both "The Glow" and "The Ray"
     */
    public function canBookCombinedSpace(Space $combinedSpace, Carbon $startDate, Carbon $endDate): bool
    {
        if (! $combinedSpace->can_combine_with || empty($combinedSpace->can_combine_with)) {
            return false;
        }

        // Get all required spaces
        $requiredSpaces = Space::whereIn('id', $combinedSpace->can_combine_with)->get();

        // All required spaces must be available
        return $this->areSpacesAvailable($requiredSpaces, $startDate, $endDate);
    }
}
