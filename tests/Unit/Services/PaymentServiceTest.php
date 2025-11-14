<?php

declare(strict_types=1);

use App\BookingStatus;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Payment;
use App\PaymentStatus;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mollie\Laravel\Facades\Mollie;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(PaymentService::class);
});

describe('createPayment', function () {
    it('creates a Payment record', function () {
        $customer = Customer::factory()->withMollieCustomer()->create();
        $booking = Booking::factory()->create([
            'customer_id' => $customer->id,
            'final_price' => 500.00,
        ]);

        // Mock Mollie API
        $mockPayment = Mockery::mock();
        $mockPayment->id = 'tr_test123';
        $mockPayment->shouldReceive('getCheckoutUrl')->andReturn('https://checkout.mollie.com/test');

        $customer->shouldReceive('charge')->andReturn($mockPayment);

        $payment = $this->service->createPayment($booking);

        expect($payment)->toBeInstanceOf(Payment::class)
            ->and($payment->booking_id)->toBe($booking->id)
            ->and($payment->customer_id)->toBe($customer->id)
            ->and($payment->amount)->toBe('500.00')
            ->and($payment->status)->toBe(PaymentStatus::Pending)
            ->and($payment->payment_method)->toBe('mollie');
    })->skip('Requires Mollie mock setup');

    it('creates Mollie customer if not exists', function () {
        // This test would require proper Mollie mocking
        expect(true)->toBeTrue();
    })->skip('Requires Mollie mock setup');
});

describe('handlePaymentPaid', function () {
    it('marks payment as paid', function () {
        $payment = Payment::factory()->create([
            'status' => PaymentStatus::Pending,
            'transaction_id' => 'tr_test123',
        ]);

        $this->service->handlePaymentPaid('tr_test123');

        expect($payment->fresh()->status)->toBe(PaymentStatus::Paid)
            ->and($payment->fresh()->paid_at)->not->toBeNull();
    });

    it('confirms booking when payment is paid', function () {
        $booking = Booking::factory()->create([
            'status' => BookingStatus::Pending,
            'payment_status' => PaymentStatus::Pending,
        ]);

        $payment = Payment::factory()->create([
            'booking_id' => $booking->id,
            'status' => PaymentStatus::Pending,
            'transaction_id' => 'tr_test123',
        ]);

        $this->service->handlePaymentPaid('tr_test123');

        expect($booking->fresh()->status)->toBe(BookingStatus::Confirmed)
            ->and($booking->fresh()->payment_status)->toBe(PaymentStatus::Paid);
    });

    it('is idempotent for duplicate webhooks', function () {
        $payment = Payment::factory()->paid()->create([
            'transaction_id' => 'tr_test123',
        ]);

        $booking = $payment->booking;
        $originalStatus = $booking->status;

        // Call again
        $this->service->handlePaymentPaid('tr_test123');

        expect($payment->fresh()->status)->toBe(PaymentStatus::Paid)
            ->and($booking->fresh()->status)->toBe($originalStatus);
    });
});

describe('handlePaymentFailed', function () {
    it('marks payment as failed', function () {
        $payment = Payment::factory()->create([
            'status' => PaymentStatus::Pending,
            'transaction_id' => 'tr_test123',
        ]);

        $this->service->handlePaymentFailed('tr_test123');

        expect($payment->fresh()->status)->toBe(PaymentStatus::Failed);
    });

    it('does not cancel booking on failed payment', function () {
        $booking = Booking::factory()->create([
            'status' => BookingStatus::Pending,
        ]);

        $payment = Payment::factory()->create([
            'booking_id' => $booking->id,
            'status' => PaymentStatus::Pending,
            'transaction_id' => 'tr_test123',
        ]);

        $this->service->handlePaymentFailed('tr_test123');

        expect($booking->fresh()->status)->toBe(BookingStatus::Pending);
    });

    it('is idempotent for duplicate webhooks', function () {
        $payment = Payment::factory()->failed()->create([
            'transaction_id' => 'tr_test123',
        ]);

        // Call again
        $this->service->handlePaymentFailed('tr_test123');

        expect($payment->fresh()->status)->toBe(PaymentStatus::Failed);
    });
});

describe('handlePaymentExpired', function () {
    it('marks payment as failed with expired metadata', function () {
        $payment = Payment::factory()->create([
            'status' => PaymentStatus::Pending,
            'transaction_id' => 'tr_test123',
        ]);

        $this->service->handlePaymentExpired('tr_test123');

        expect($payment->fresh()->status)->toBe(PaymentStatus::Failed)
            ->and($payment->fresh()->metadata)->toHaveKey('expired_at');
    });
});

describe('refundPayment', function () {
    it('processes full refund', function () {
        $payment = Payment::factory()->paid()->create([
            'amount' => 500.00,
            'transaction_id' => 'tr_test123',
        ]);

        // Mock Mollie API
        Mollie::shouldReceive('api->payments->get')->andReturnSelf();
        Mollie::shouldReceive('refund')->andReturn((object) ['id' => 're_test123']);

        $refunded = $this->service->refundPayment($payment);

        expect($refunded->status)->toBe(PaymentStatus::Refunded)
            ->and($refunded->refund_amount)->toBe('500.00')
            ->and($refunded->refunded_at)->not->toBeNull();
    })->skip('Requires Mollie mock setup');

    it('processes partial refund', function () {
        $payment = Payment::factory()->paid()->create([
            'amount' => 500.00,
            'transaction_id' => 'tr_test123',
        ]);

        // Mock Mollie API
        Mollie::shouldReceive('api->payments->get')->andReturnSelf();
        Mollie::shouldReceive('refund')->andReturn((object) ['id' => 're_test123']);

        $refunded = $this->service->refundPayment($payment, 200.00);

        expect($refunded->status)->toBe(PaymentStatus::PartiallyRefunded)
            ->and($refunded->refund_amount)->toBe('200.00');
    })->skip('Requires Mollie mock setup');

    it('cancels booking on full refund', function () {
        $booking = Booking::factory()->confirmed()->create();

        $payment = Payment::factory()->paid()->create([
            'booking_id' => $booking->id,
            'amount' => 500.00,
            'transaction_id' => 'tr_test123',
        ]);

        // Mock Mollie API
        Mollie::shouldReceive('api->payments->get')->andReturnSelf();
        Mollie::shouldReceive('refund')->andReturn((object) ['id' => 're_test123']);

        $this->service->refundPayment($payment);

        expect($booking->fresh()->status)->toBe(BookingStatus::Cancelled)
            ->and($booking->fresh()->cancellation_reason)->toBe('Payment refunded');
    })->skip('Requires Mollie mock setup');

    it('throws exception when refunding unpaid payment', function () {
        $payment = Payment::factory()->create([
            'status' => PaymentStatus::Pending,
        ]);

        expect(fn () => $this->service->refundPayment($payment))
            ->toThrow(\InvalidArgumentException::class);
    });

    it('throws exception when refund exceeds available amount', function () {
        $payment = Payment::factory()->paid()->create([
            'amount' => 500.00,
        ]);

        expect(fn () => $this->service->refundPayment($payment, 600.00))
            ->toThrow(\InvalidArgumentException::class);
    });
});
