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
            'capacity' => 6,
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
            'capacity' => 10,
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
            'capacity' => 16,
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
            'price' => 200.00,
            'is_active' => true,
            'valid_from' => $validFrom,
        ]);

        PricingRule::create([
            'space_id' => $theGlow->id,

            'booking_type' => BookingType::Conferentie,
            'duration_type' => DurationType::FullDay,
            'price' => 380.00,
            'is_active' => true,
            'valid_from' => $validFrom,
        ]);

        PricingRule::create([
            'space_id' => $theGlow->id,

            'booking_type' => BookingType::Conferentie,
            'duration_type' => DurationType::Quarterly,
            'price' => 200.00,
            'discount_percentage' => 10.00,
            'is_active' => true,
            'valid_from' => $validFrom,
        ]);

        PricingRule::create([
            'space_id' => $theGlow->id,

            'booking_type' => BookingType::Conferentie,
            'duration_type' => DurationType::Quarterly,
            'price' => 380.00,
            'discount_percentage' => 10.00,
            'is_active' => true,
            'valid_from' => $validFrom,
        ]);

        // The Ray pricing
        PricingRule::create([
            'space_id' => $theRay->id,

            'booking_type' => BookingType::Conferentie,
            'duration_type' => DurationType::HalfDay,
            'price' => 275.00,
            'is_active' => true,
            'valid_from' => $validFrom,
        ]);

        PricingRule::create([
            'space_id' => $theRay->id,

            'booking_type' => BookingType::Conferentie,
            'duration_type' => DurationType::FullDay,
            'price' => 500.00,
            'is_active' => true,
            'valid_from' => $validFrom,
        ]);

        PricingRule::create([
            'space_id' => $theRay->id,

            'booking_type' => BookingType::Conferentie,
            'duration_type' => DurationType::Quarterly,
            'price' => 275.00,
            'discount_percentage' => 10.00,
            'is_active' => true,
            'valid_from' => $validFrom,
        ]);

        PricingRule::create([
            'space_id' => $theRay->id,

            'booking_type' => BookingType::Conferentie,
            'duration_type' => DurationType::Quarterly,
            'price' => 500.00,
            'discount_percentage' => 10.00,
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
            'price' => 400.00,
            'discount_percentage' => 10.00,
            'is_active' => true,
            'valid_from' => $validFrom,
        ]);

        PricingRule::create([
            'space_id' => $theUniverse->id,

            'booking_type' => BookingType::Conferentie,
            'duration_type' => DurationType::Quarterly,
            'price' => 700.00,
            'discount_percentage' => 10.00,
            'is_active' => true,
            'valid_from' => $validFrom,
        ]);

        // === B&B / Accommodation Pricing ===

        // The Sun (single room)
        PricingRule::create([
            'space_id' => $theSun->id,

            'booking_type' => BookingType::Accommodation,
            'duration_type' => DurationType::Night,
            'price' => 110.00,
            'max_people' => 2,
            'is_active' => true,
            'valid_from' => $validFrom,
        ]);

        // The Moon (single room)
        PricingRule::create([
            'space_id' => $theMoon->id,

            'booking_type' => BookingType::Accommodation,
            'duration_type' => DurationType::Night,
            'price' => 110.00,
            'max_people' => 2,
            'is_active' => true,
            'valid_from' => $validFrom,
        ]);

        // Both rooms package (4 people total)
        // Note: This will need special booking logic to book both spaces together
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

        // Private full rental (both rooms, exclusive use)
        PricingRule::create([
            'space_id' => $theSun->id,

            'booking_type' => BookingType::Package,
            'duration_type' => DurationType::Night,
            'price' => 320.00,
            'is_active' => true,
            'valid_from' => $validFrom,
        ]);

        // Weekend package (Friday-Sunday including light therapy)
        PricingRule::create([
            'space_id' => $theSun->id,

            'booking_type' => BookingType::Package,
            'duration_type' => DurationType::Night,
            'price' => 160.00, // 110 + 50 per person
            'max_people' => 2,
            'is_active' => true,
            'valid_from' => $validFrom,
        ]);

        PricingRule::create([
            'space_id' => $theMoon->id,

            'booking_type' => BookingType::Package,
            'duration_type' => DurationType::Night,
            'price' => 160.00, // 110 + 50 per person
            'max_people' => 2,
            'is_active' => true,
            'valid_from' => $validFrom,
        ]);

        // === Co-Working Pricing ===

        // Day pass
        PricingRule::create([
            'space_id' => $coWorking->id,

            'booking_type' => BookingType::CoWorking,
            'duration_type' => DurationType::DayPass,
            'price' => 35.00,
            'is_active' => true,
            'valid_from' => $validFrom,
        ]);

        // Monthly subscriptions
        PricingRule::create([
            'space_id' => $coWorking->id,

            'booking_type' => BookingType::CoWorking,
            'duration_type' => DurationType::Weekly,
            'price' => 120.00,
            'is_active' => true,
            'valid_from' => $validFrom,
        ]);

        PricingRule::create([
            'space_id' => $coWorking->id,

            'booking_type' => BookingType::CoWorking,
            'duration_type' => DurationType::Monthly,
            'price' => 300.00,
            'is_active' => true,
            'valid_from' => $validFrom,
        ]);

        PricingRule::create([
            'space_id' => $coWorking->id,

            'booking_type' => BookingType::CoWorking,
            'duration_type' => DurationType::Monthly,
            'price' => 450.00,
            'is_active' => true,
            'valid_from' => $validFrom,
        ]);

        // === Light Therapy Pricing ===

        // Private session
        PricingRule::create([
            'space_id' => $lightCenter->id,

            'booking_type' => BookingType::LightTherapy,
            'duration_type' => DurationType::Session,
            'price' => 120.00,
            'is_active' => true,
            'valid_from' => $validFrom,
        ]);

        // Night arrangement (overnight + session)
        PricingRule::create([
            'space_id' => $lightCenter->id,

            'booking_type' => BookingType::LightTherapy,
            'duration_type' => DurationType::Night,
            'price' => 440.00,
            'is_active' => true,
            'valid_from' => $validFrom,
        ]);

        // Monthly subscription (4 sessions)
        PricingRule::create([
            'space_id' => $lightCenter->id,

            'booking_type' => BookingType::LightTherapy,
            'duration_type' => DurationType::Monthly,
            'price' => 400.00,
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
