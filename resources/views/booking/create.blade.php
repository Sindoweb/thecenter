<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ __('booking.title') }} - {{ config('app.name', 'The Center') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Styles / Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-gray-50 text-gray-900 antialiased" style="font-family: 'Inter', sans-serif;">
        <!-- Header with Language Switcher -->
        <header class="sticky top-0 z-50 w-full bg-white/95 backdrop-blur-sm border-b border-gray-200">
            <div class="max-w-6xl mx-auto px-6 lg:px-8 py-5 flex justify-between items-center">
                <a href="{{ route('home', ['locale' => app()->getLocale()]) }}" class="text-2xl lg:text-3xl font-bold text-gray-900 hover:opacity-80 transition-opacity">
                    {{ config('app.name', 'The Center') }}
                </a>
                <x-language-switcher />
            </div>
        </header>

        <!-- Main Content -->
        <main class="max-w-4xl mx-auto px-6 lg:px-8 py-12 lg:py-16">
            <!-- Page Header -->
            <div class="text-center mb-12">
                <h1 class="text-4xl lg:text-5xl font-bold text-gray-900 mb-4">
                    {{ __('booking.title') }}
                </h1>
                <p class="text-xl text-gray-600">
                    {{ __('booking.subtitle') }}
                </p>
            </div>

            <!-- Booking Form -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-8 lg:p-12">
                <form id="bookingForm" class="space-y-8">
                    @csrf

                    <!-- Space Selection -->
                    <div>
                        <label for="space_id" class="block text-sm font-semibold text-gray-900 mb-2">
                            {{ __('booking.space_selection') }} *
                        </label>
                        <select
                            id="space_id"
                            name="space_id"
                            required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-offset-2 transition-all"
                            style="outline: none;"
                            onchange="updateBookingOptions()"
                        >
                            <option value="">{{ __('booking.space_placeholder') }}</option>
                            @foreach($spaces as $type => $typeSpaces)
                                <optgroup label="{{ ucfirst(str_replace('_', ' ', $type)) }}">
                                    @foreach($typeSpaces as $space)
                                        <option value="{{ $space->id }}" data-type="{{ $space->type->value }}">
                                            {{ $space->name }} ({{ $space->capacity }} people)
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>

                    <!-- Booking Type and Duration -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="booking_type" class="block text-sm font-semibold text-gray-900 mb-2">
                                {{ __('booking.booking_type') }} *
                            </label>
                            <select
                                id="booking_type"
                                name="booking_type"
                                required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-offset-2 transition-all"
                                style="outline: none;"
                                onchange="updatePricing()"
                            >
                                <option value="">Choose type...</option>
                                @foreach($bookingTypes as $type)
                                    <option value="{{ $type->value }}">{{ ucfirst(str_replace('_', ' ', $type->value)) }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="duration_type" class="block text-sm font-semibold text-gray-900 mb-2">
                                {{ __('booking.duration_type') }} *
                            </label>
                            <select
                                id="duration_type"
                                name="duration_type"
                                required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-offset-2 transition-all"
                                style="outline: none;"
                                onchange="updatePricing()"
                            >
                                <option value="">Choose duration...</option>
                                @foreach($durationTypes as $duration)
                                    <option value="{{ $duration->value }}">{{ ucfirst(str_replace('_', ' ', $duration->value)) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Date Selection -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="start_date" class="block text-sm font-semibold text-gray-900 mb-2">
                                {{ __('booking.start_date') }} *
                            </label>
                            <input
                                type="date"
                                id="start_date"
                                name="start_date"
                                required
                                min="{{ date('Y-m-d') }}"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-offset-2 transition-all"
                                style="outline: none;"
                                onchange="checkAvailability()"
                            />
                        </div>

                        <div>
                            <label for="end_date" class="block text-sm font-semibold text-gray-900 mb-2">
                                {{ __('booking.end_date') }} *
                            </label>
                            <input
                                type="date"
                                id="end_date"
                                name="end_date"
                                required
                                min="{{ date('Y-m-d') }}"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-offset-2 transition-all"
                                style="outline: none;"
                                onchange="checkAvailability()"
                            />
                        </div>
                    </div>

                    <!-- People Count -->
                    <div>
                        <label for="people_count" class="block text-sm font-semibold text-gray-900 mb-2">
                            {{ __('booking.people_count') }}
                        </label>
                        <input
                            type="number"
                            id="people_count"
                            name="people_count"
                            min="1"
                            placeholder="Optional"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-offset-2 transition-all"
                            style="outline: none;"
                        />
                    </div>

                    <!-- Availability Status -->
                    <div id="availabilityStatus" class="hidden">
                        <div class="rounded-lg p-4 text-center font-medium"></div>
                    </div>

                    <!-- Pricing Display -->
                    <div id="pricingDisplay" class="hidden bg-gray-50 rounded-lg p-6">
                        <h3 class="text-lg font-bold text-gray-900 mb-4">Pricing</h3>
                        <div class="space-y-2">
                            <div class="flex justify-between text-gray-600">
                                <span>{{ __('booking.price') }}:</span>
                                <span id="basePrice" class="font-semibold">€0.00</span>
                            </div>
                            <div id="discountRow" class="hidden flex justify-between text-green-600">
                                <span>{{ __('booking.discount') }}:</span>
                                <span id="discountAmount" class="font-semibold">-€0.00</span>
                            </div>
                            <div class="pt-2 border-t border-gray-300 flex justify-between text-lg font-bold text-gray-900">
                                <span>{{ __('booking.total') }}:</span>
                                <span id="totalPrice">€0.00</span>
                            </div>
                        </div>
                    </div>

                    <!-- Customer Details -->
                    <div class="space-y-6">
                        <h3 class="text-xl font-bold text-gray-900">{{ __('booking.customer_details') }}</h3>

                        <div>
                            <label for="customer_name" class="block text-sm font-semibold text-gray-900 mb-2">
                                {{ __('booking.name') }} *
                            </label>
                            <input
                                type="text"
                                id="customer_name"
                                name="customer_name"
                                required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-offset-2 transition-all"
                                style="outline: none;"
                            />
                        </div>

                        <div>
                            <label for="customer_email" class="block text-sm font-semibold text-gray-900 mb-2">
                                {{ __('booking.email') }} *
                            </label>
                            <input
                                type="email"
                                id="customer_email"
                                name="customer_email"
                                required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-offset-2 transition-all"
                                style="outline: none;"
                            />
                        </div>

                        <div>
                            <label for="customer_phone" class="block text-sm font-semibold text-gray-900 mb-2">
                                {{ __('booking.phone') }}
                            </label>
                            <input
                                type="tel"
                                id="customer_phone"
                                name="customer_phone"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-offset-2 transition-all"
                                style="outline: none;"
                            />
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="pt-6">
                        <button
                            type="submit"
                            class="w-full px-8 py-4 text-white rounded-lg font-semibold transition-all duration-200 shadow-sm hover:shadow-md"
                            style="background-color: rgb(140 198 62);"
                            onmouseover="this.style.backgroundColor='rgb(120 178 42)'"
                            onmouseout="this.style.backgroundColor='rgb(140 198 62)'"
                        >
                            {{ __('booking.proceed_to_payment') }}
                        </button>
                    </div>
                </form>
            </div>
        </main>

        <script>
            // Store the green color for focus states
            const greenColor = 'rgb(140 198 62)';

            // Add green focus ring to all inputs
            document.querySelectorAll('input, select').forEach(element => {
                element.addEventListener('focus', function() {
                    this.style.borderColor = greenColor;
                    this.style.boxShadow = `0 0 0 3px rgba(140, 198, 62, 0.1)`;
                });
                element.addEventListener('blur', function() {
                    this.style.borderColor = '';
                    this.style.boxShadow = '';
                });
            });

            function updateBookingOptions() {
                // Reset other fields when space changes
                document.getElementById('booking_type').value = '';
                document.getElementById('duration_type').value = '';
                document.getElementById('pricingDisplay').classList.add('hidden');
            }

            async function checkAvailability() {
                const spaceId = document.getElementById('space_id').value;
                const startDate = document.getElementById('start_date').value;
                const endDate = document.getElementById('end_date').value;

                if (!spaceId || !startDate || !endDate) {
                    return;
                }

                const statusDiv = document.getElementById('availabilityStatus');
                const statusContent = statusDiv.querySelector('div');
                statusDiv.classList.remove('hidden');
                statusContent.className = 'rounded-lg p-4 text-center font-medium bg-gray-100 text-gray-600';
                statusContent.textContent = '{{ __("booking.checking") }}';

                try {
                    const response = await fetch('{{ route("booking.check-availability", ["locale" => app()->getLocale()]) }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            space_id: spaceId,
                            start_date: startDate,
                            end_date: endDate
                        })
                    });

                    const data = await response.json();

                    if (data.available) {
                        statusContent.className = 'rounded-lg p-4 text-center font-medium bg-green-50 text-green-700';
                        statusContent.textContent = '✓ {{ __("booking.available") }}';
                    } else {
                        statusContent.className = 'rounded-lg p-4 text-center font-medium bg-red-50 text-red-700';
                        statusContent.textContent = '✗ {{ __("booking.not_available") }}';
                    }
                } catch (error) {
                    console.error('Error checking availability:', error);
                }
            }

            async function updatePricing() {
                const spaceId = document.getElementById('space_id').value;
                const bookingType = document.getElementById('booking_type').value;
                const durationType = document.getElementById('duration_type').value;
                const peopleCount = document.getElementById('people_count').value;

                if (!spaceId || !bookingType || !durationType) {
                    document.getElementById('pricingDisplay').classList.add('hidden');
                    return;
                }

                try {
                    const response = await fetch('{{ route("booking.get-pricing", ["locale" => app()->getLocale()]) }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            space_id: spaceId,
                            booking_type: bookingType,
                            duration_type: durationType,
                            people_count: peopleCount
                        })
                    });

                    const data = await response.json();

                    if (response.ok) {
                        document.getElementById('basePrice').textContent = '€' + parseFloat(data.price).toFixed(2);
                        document.getElementById('totalPrice').textContent = '€' + parseFloat(data.discounted_price).toFixed(2);

                        if (data.discount_percentage && data.discount_percentage > 0) {
                            const discount = data.price - data.discounted_price;
                            document.getElementById('discountAmount').textContent = '-€' + parseFloat(discount).toFixed(2);
                            document.getElementById('discountRow').classList.remove('hidden');
                        } else {
                            document.getElementById('discountRow').classList.add('hidden');
                        }

                        document.getElementById('pricingDisplay').classList.remove('hidden');
                    } else {
                        document.getElementById('pricingDisplay').classList.add('hidden');
                        alert(data.error || '{{ __("booking.errors.no_pricing") }}');
                    }
                } catch (error) {
                    console.error('Error fetching pricing:', error);
                }
            }
        </script>
    </body>
</html>
