<?php

use App\DurationType;
use App\Models\PricingRule;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update existing quarterly pricing rules with base_duration_type
        // based on their price matching the half-day or full-day base price

        $quarterlyRules = PricingRule::where('duration_type', DurationType::Quarterly)->get();

        foreach ($quarterlyRules as $rule) {
            // Find corresponding base price rule for the same space and booking type
            $halfDayRule = PricingRule::where('space_id', $rule->space_id)
                ->where('booking_type', $rule->booking_type)
                ->where('duration_type', DurationType::HalfDay)
                ->first();

            $fullDayRule = PricingRule::where('space_id', $rule->space_id)
                ->where('booking_type', $rule->booking_type)
                ->where('duration_type', DurationType::FullDay)
                ->first();

            // Match quarterly rule price to base price
            if ($halfDayRule && $rule->price == $halfDayRule->price) {
                $rule->base_duration_type = DurationType::HalfDay;
                $rule->save();
            } elseif ($fullDayRule && $rule->price == $fullDayRule->price) {
                $rule->base_duration_type = DurationType::FullDay;
                $rule->save();
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Set base_duration_type back to null for quarterly rules
        PricingRule::where('duration_type', DurationType::Quarterly)
            ->update(['base_duration_type' => null]);
    }
};
