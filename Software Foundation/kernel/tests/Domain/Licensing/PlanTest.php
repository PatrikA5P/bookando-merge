<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Tests\Domain\Licensing;

use PHPUnit\Framework\TestCase;
use SoftwareFoundation\Kernel\Domain\Licensing\Plan;

final class PlanTest extends TestCase
{
    private function professionalPlan(): Plan
    {
        return new Plan(
            id: 'professional',
            name: 'Professional',
            modules: ['booking', 'customers', 'finance'],
            features: ['export_csv', 'api_write'],
            quotas: ['bookings_per_month' => 1000, 'api_calls' => -1],
            integrations: ['stripe', 'google_calendar'],
            maxSeats: 10,
        );
    }

    public function test_includes_module(): void
    {
        $plan = $this->professionalPlan();
        $this->assertTrue($plan->includesModule('booking'));
        $this->assertTrue($plan->includesModule('finance'));
        $this->assertFalse($plan->includesModule('reporting'));
    }

    public function test_has_feature(): void
    {
        $plan = $this->professionalPlan();
        $this->assertTrue($plan->hasFeature('export_csv'));
        $this->assertFalse($plan->hasFeature('white_label'));
    }

    public function test_has_integration(): void
    {
        $plan = $this->professionalPlan();
        $this->assertTrue($plan->hasIntegration('stripe'));
        $this->assertFalse($plan->hasIntegration('paypal'));
    }

    public function test_quota_limit(): void
    {
        $plan = $this->professionalPlan();
        $this->assertSame(1000, $plan->quotaLimit('bookings_per_month'));
        $this->assertSame(-1, $plan->quotaLimit('api_calls'));
        $this->assertSame(0, $plan->quotaLimit('unknown_quota'));
    }

    public function test_is_unlimited_quota(): void
    {
        $plan = $this->professionalPlan();
        $this->assertTrue($plan->isUnlimitedQuota('api_calls'));
        $this->assertFalse($plan->isUnlimitedQuota('bookings_per_month'));
    }

    public function test_max_seats(): void
    {
        $plan = $this->professionalPlan();
        $this->assertSame(10, $plan->maxSeats);
        $this->assertFalse($plan->isUnlimitedSeats());
    }

    public function test_unlimited_seats(): void
    {
        $plan = new Plan(
            id: 'enterprise',
            name: 'Enterprise',
            modules: [],
            features: [],
            quotas: [],
            integrations: [],
            maxSeats: -1,
        );
        $this->assertTrue($plan->isUnlimitedSeats());
    }
}
