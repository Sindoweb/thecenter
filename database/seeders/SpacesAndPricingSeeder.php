<?php

namespace Database\Seeders;

use App\BookingType;
use App\DurationType;
use App\Models\PricingRule;
use App\Models\Space;
use App\SpaceType;
use Illuminate\Database\Seeder;

class SpacesAndPricingSeeder extends Seeder
{
    public function run(): void
    {
        // Create conference rooms first (they'll be referenced by The Universe)
        $theGlow = Space::create([
            'name' => 'The Glow',
            'slug' => 'the-glow',
            'description' => 'Intimate conference room perfect for small meetings and focused work sessions. Can be combined with The Ray to form The Universe, or used as The Sun for accommodation.',
            'type' => SpaceType::ConferenceRoom,
            'capacity' => 8,
            'features' => [
                'WiFi',
                'Projector',
                'Whiteboard',
                'Video Conferencing',
                'Natural Light',
                'Coffee/Tea Station',
            ],
            'is_active' => true,
            'sort_order' => 10,
        ]);

        $theRay = Space::create([
            'name' => 'The Ray',
            'slug' => 'the-ray',
            'description' => 'Spacious conference room ideal for medium-sized meetings. Can be combined with The Glow to form The Universe, or used as The Moon for accommodation.',
            'type' => SpaceType::ConferenceRoom,
            'capacity' => 12,
            'features' => [
                'WiFi',
                'Large Screen Display',
                'Whiteboard',
                'Video Conferencing',
                'Natural Light',
                'Coffee/Tea Station',
            ],
            'is_active' => true,
            'sort_order' => 20,
        ]);

        // Update both spaces with can_combine_with references
        $theGlow->update([
            'can_combine_with' => [$theRay->id],
        ]);

        $theRay->update([
            'can_combine_with' => [$theGlow->id],
        ]);

        // Create The Universe (combined space)
        $theUniverse = Space::create([
            'name' => 'The Universe',
            'slug' => 'the-universe',
            'description' => 'Our largest conference space, created by combining The Glow and The Ray. Perfect for larger meetings, workshops, and events.',
            'type' => SpaceType::Combined,
            'capacity' => 20,
            'features' => [
                'WiFi',
                'Multiple Displays',
                'Projector',
                'Whiteboards',
                'Video Conferencing',
                'Natural Light',
                'Coffee/Tea Station',
                'Flexible Layout',
            ],
            'can_combine_with' => [$theGlow->id, $theRay->id],
            'is_active' => true,
            'sort_order' => 30,
        ]);

        // Create accommodation spaces (B&B)
        $theSun = Space::create([
            'name' => 'The Sun',
            'slug' => 'the-sun',
            'description' => 'Cozy accommodation space (The Glow configured for overnight stays). Perfect for couples or solo travelers seeking a peaceful retreat.',
            'type' => SpaceType::Accommodation,
            'capacity' => 2,
            'features' => [
                'Queen Bed',
                'Private Bathroom',
                'WiFi',
                'Kitchenette',
                'Breakfast Included',
                'Natural Light',
            ],
            'is_active' => true,
            'sort_order' => 40,
        ]);

        $theMoon = Space::create([
            'name' => 'The Moon',
            'slug' => 'the-moon',
            'description' => 'Comfortable accommodation space (The Ray configured for overnight stays). Ideal for couples or solo travelers looking for rest and rejuvenation.',
            'type' => SpaceType::Accommodation,
            'capacity' => 2,
            'features' => [
                'Queen Bed',
                'Private Bathroom',
                'WiFi',
                'Kitchenette',
                'Breakfast Included',
                'Natural Light',
            ],
            'is_active' => true,
            'sort_order' => 50,
        ]);

        // Create co-working space
        $coWorking = Space::create([
            'name' => 'Co-Working Area',
            'slug' => 'co-working',
            'description' => 'Flexible co-working space with high-speed internet and professional amenities. Can expand to The Glow and The Ray when available.',
            'type' => SpaceType::CoWorking,
            'capacity' => 6,
            'features' => [
                'High-Speed WiFi',
                'Hot Desks',
                'Ergonomic Chairs',
                'Coffee/Tea',
                'Printing',
                'Phone Booths',
                'Natural Light',
            ],
            'is_active' => true,
            'sort_order' => 60,
        ]);

        // Create light therapy room
        $lightCenter = Space::create([
            'name' => 'The Light Center',
            'slug' => 'the-light-center',
            'description' => 'Specialized light therapy room for individual or couples sessions. Designed for relaxation, rejuvenation, and therapeutic treatments.',
            'type' => SpaceType::TherapyRoom,
            'capacity' => 2,
            'features' => [
                'Light Therapy Equipment',
                'Comfortable Seating',
                'Ambient Sound System',
                'Climate Control',
                'Private Space',
                'Relaxation Area',
            ],
            'is_active' => true,
            'sort_order' => 70,
        ]);

        // Now create pricing rules
        $validFrom = now()->startOfDay();

        // === Conference Room Pricing ===

        // The Glow pricing
        PricingRule::create([
            'space_id' => $theGlow->id,
            'booking_type' => BookingType::Conferentie,
            'duration_type' => DurationType::HalfDay,
            'price' => 140.00,
            'is_active' => true,
            'valid_from' => $validFrom,
        ]);

        PricingRule::create([
            'space_id' => $theGlow->id,
            'booking_type' => BookingType::Conferentie,
            'duration_type' => DurationType::FullDay,
            'price' => 250.00,
            'is_active' => true,
            'valid_from' => $validFrom,
        ]);

        PricingRule::create([
            'space_id' => $theGlow->id,
            'booking_type' => BookingType::Conferentie,
            'duration_type' => DurationType::Quarterly,
            'base_duration_type' => DurationType::HalfDay,
            'price' => 140.00,
            'discount_percentage' => 20.00,
            'is_active' => true,
            'valid_from' => $validFrom,
        ]);

        PricingRule::create([
            'space_id' => $theGlow->id,
            'booking_type' => BookingType::Conferentie,
            'duration_type' => DurationType::Quarterly,
            'base_duration_type' => DurationType::FullDay,
            'price' => 250.00,
            'discount_percentage' => 20.00,
            'is_active' => true,
            'valid_from' => $validFrom,
        ]);

        // The Ray pricing
        PricingRule::create([
            'space_id' => $theRay->id,
            'booking_type' => BookingType::Conferentie,
            'duration_type' => DurationType::HalfDay,
            'price' => 160.00,
            'is_active' => true,
            'valid_from' => $validFrom,
        ]);

        PricingRule::create([
            'space_id' => $theRay->id,
            'booking_type' => BookingType::Conferentie,
            'duration_type' => DurationType::FullDay,
            'price' => 300.00,
            'is_active' => true,
            'valid_from' => $validFrom,
        ]);

        PricingRule::create([
            'space_id' => $theRay->id,
            'booking_type' => BookingType::Conferentie,
            'duration_type' => DurationType::Quarterly,
            'base_duration_type' => DurationType::HalfDay,
            'price' => 160.00,
            'discount_percentage' => 20.00,
            'is_active' => true,
            'valid_from' => $validFrom,
        ]);

        PricingRule::create([
            'space_id' => $theRay->id,
            'booking_type' => BookingType::Conferentie,
            'duration_type' => DurationType::Quarterly,
            'base_duration_type' => DurationType::FullDay,
            'price' => 300.00,
            'discount_percentage' => 20.00,
            'is_active' => true,
            'valid_from' => $validFrom,
        ]);

        // The Universe pricing
        PricingRule::create([
            'space_id' => $theUniverse->id,

            'booking_type' => BookingType::Conferentie,
            'duration_type' => DurationType::HalfDay,
            'price' => 400.00,
            'is_active' => true,
            'valid_from' => $validFrom,
        ]);

        PricingRule::create([
            'space_id' => $theUniverse->id,

            'booking_type' => BookingType::Conferentie,
            'duration_type' => DurationType::FullDay,
            'price' => 700.00,
            'is_active' => true,
            'valid_from' => $validFrom,
        ]);

        PricingRule::create([
            'space_id' => $theUniverse->id,
            'booking_type' => BookingType::Conferentie,
            'duration_type' => DurationType::Quarterly,
            'base_duration_type' => DurationType::HalfDay,
            'price' => 400.00,
            'discount_percentage' => 20.00,
            'is_active' => true,
            'valid_from' => $validFrom,
        ]);

        PricingRule::create([
            'space_id' => $theUniverse->id,
            'booking_type' => BookingType::Conferentie,
            'duration_type' => DurationType::Quarterly,
            'base_duration_type' => DurationType::FullDay,
            'price' => 700.00,
            'discount_percentage' => 20.00,
            'is_active' => true,
            'valid_from' => $validFrom,
        ]);

        // === B&B / Accommodation Pricing ===

        // The Sun (single room, 1 night, up to 2 people)
        PricingRule::create([
            'space_id' => $theSun->id,
            'booking_type' => BookingType::Accommodation,
            'duration_type' => DurationType::Night,
            'price' => 110.00,
            'max_people' => 2,
            'is_active' => true,
            'valid_from' => $validFrom,
        ]);

        // The Moon (single room, 1 night, up to 2 people)
        PricingRule::create([
            'space_id' => $theMoon->id,
            'booking_type' => BookingType::Accommodation,
            'duration_type' => DurationType::Night,
            'price' => 110.00,
            'max_people' => 2,
            'is_active' => true,
            'valid_from' => $validFrom,
        ]);

        // Both rooms package (2 rooms, 1 night, up to 4 people)
        PricingRule::create([
            'space_id' => $theSun->id,
            'booking_type' => BookingType::Package,
            'duration_type' => DurationType::Night,
            'price' => 200.00,
            'min_people' => 3,
            'max_people' => 4,
            'is_active' => true,
            'valid_from' => $validFrom,
        ]);

        // Weekend package: The Sun (Fri-Sun) + light therapy
        // Base accommodation + €50 per person per 2-hour light therapy session
        // Price shown is base accommodation only; light therapy sessions added separately
        PricingRule::create([
            'space_id' => $theSun->id,
            'booking_type' => BookingType::Package,
            'duration_type' => DurationType::Night,
            'price' => 110.00, // Base price, light therapy sessions charged at +€50/person/session
            'max_people' => 2,
            'is_active' => true,
            'valid_from' => $validFrom,
        ]);

        // Weekend package: The Moon (Fri-Sun) + light therapy
        // Base accommodation + €50 per person per 2-hour light therapy session
        // Price shown is base accommodation only; light therapy sessions added separately
        PricingRule::create([
            'space_id' => $theMoon->id,
            'booking_type' => BookingType::Package,
            'duration_type' => DurationType::Night,
            'price' => 110.00, // Base price, light therapy sessions charged at +€50/person/session
            'max_people' => 2,
            'is_active' => true,
            'valid_from' => $validFrom,
        ]);

        // === Co-Working Pricing ===

        // Day pass (one-time booking)
        PricingRule::create([
            'space_id' => $coWorking->id,
            'booking_type' => BookingType::CoWorking,
            'duration_type' => DurationType::DayPass,
            'price' => 35.00,
            'is_active' => true,
            'valid_from' => $validFrom,
        ]);

        // Monthly subscription: 1 day per week (4 days/month, usage_limit: 4)
        PricingRule::create([
            'space_id' => $coWorking->id,
            'booking_type' => BookingType::CoWorking,
            'duration_type' => DurationType::Monthly,
            'price' => 120.00,
            'min_people' => 1, // Used to identify subscription tier
            'max_people' => 1,
            'is_active' => true,
            'valid_from' => $validFrom,
        ]);

        // Monthly subscription: 3 days per week (12 days/month, usage_limit: 12)
        PricingRule::create([
            'space_id' => $coWorking->id,
            'booking_type' => BookingType::CoWorking,
            'duration_type' => DurationType::Monthly,
            'price' => 300.00,
            'min_people' => 3, // Used to identify subscription tier
            'max_people' => 3,
            'is_active' => true,
            'valid_from' => $validFrom,
        ]);

        // Monthly subscription: Unlimited (mon-fri, no usage_limit)
        PricingRule::create([
            'space_id' => $coWorking->id,
            'booking_type' => BookingType::CoWorking,
            'duration_type' => DurationType::Monthly,
            'price' => 450.00,
            'min_people' => 999, // Used to identify unlimited tier
            'is_active' => true,
            'valid_from' => $validFrom,
        ]);

        // === Light Therapy Pricing ===

        // Private session (2 hours, one-time)
        PricingRule::create([
            'space_id' => $lightCenter->id,
            'booking_type' => BookingType::LightTherapy,
            'duration_type' => DurationType::Session,
            'price' => 120.00,
            'is_active' => true,
            'valid_from' => $validFrom,
        ]);

        // Private session subscription (4 sessions per month)
        PricingRule::create([
            'space_id' => $lightCenter->id,
            'booking_type' => BookingType::LightTherapy,
            'duration_type' => DurationType::Monthly,
            'price' => 400.00,
            'min_people' => 1, // Used to identify session-only subscription
            'is_active' => true,
            'valid_from' => $validFrom,
        ]);

        // Private overnight package (accommodation + session, one-time)
        PricingRule::create([
            'space_id' => $lightCenter->id,
            'booking_type' => BookingType::LightTherapy,
            'duration_type' => DurationType::Night,
            'price' => 440.00,
            'is_active' => true,
            'valid_from' => $validFrom,
        ]);

        // Private overnight package subscription (4 overnight sessions per month)
        PricingRule::create([
            'space_id' => $lightCenter->id,
            'booking_type' => BookingType::LightTherapy,
            'duration_type' => DurationType::Monthly,
            'price' => 1600.00,
            'min_people' => 2, // Used to identify overnight package subscription
            'is_active' => true,
            'valid_from' => $validFrom,
        ]);

        $this->command->info('Successfully seeded spaces and pricing rules!');
        $this->command->info("Created {$this->countSpaces()} spaces and {$this->countPricingRules()} pricing rules.");
    }

    private function countSpaces(): int
    {
        return Space::count();
    }

    private function countPricingRules(): int
    {
        return PricingRule::count();
    }
}
