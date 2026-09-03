<?php

namespace Tests\Unit;

use App\Support\BudgetForecast;
use PHPUnit\Framework\TestCase;

class BudgetForecastTest extends TestCase
{
    public function test_burn_rate_projected_cost_and_over_budget_warning(): void
    {
        $now = new \DateTimeImmutable('2026-09-11');
        $result = BudgetForecast::compute(
            10000,
            '2026-09-01',
            '2026-09-21',
            [
                ['amount' => 6000, 'category' => 'materials'],
            ],
            $now,
        );

        $this->assertSame(10, $result['elapsed_days']);
        $this->assertEquals(600.0, $result['burn_per_day']);
        $this->assertEquals(12000.0, $result['projected_final_cost']);
        $this->assertSame(6, $result['runway_days']);
        $this->assertTrue($result['will_exceed']);
        $this->assertSame('Materials', $result['categories'][0]['label']);
    }

    public function test_on_track_spend_does_not_warn(): void
    {
        $now = new \DateTimeImmutable('2026-09-11');
        $result = BudgetForecast::compute(
            10000,
            '2026-09-01',
            '2026-09-21',
            [
                ['amount' => 4000, 'category' => 'labor'],
            ],
            $now,
        );

        $this->assertEquals(8000.0, $result['projected_final_cost']);
        $this->assertFalse($result['will_exceed']);
        $this->assertNull($result['projected_over_date']);
        $this->assertSame('Labor', $result['categories'][0]['label']);
    }
}
