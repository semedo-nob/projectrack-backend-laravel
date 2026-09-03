<?php

namespace App\Support;

final class BudgetForecast
{
    /**
     * @param  iterable<array{amount?: float|int|null, date?: mixed, category?: string|null}>  $expenses
     * @return array{
     *     spent: float,
     *     budget: float,
     *     remaining: float,
     *     elapsed_days: int,
     *     burn_per_day: float,
     *     projected_final_cost: float,
     *     runway_days: int|null,
     *     will_exceed: bool,
     *     projected_over_date: string|null,
     *     used_percent: float,
     *     projected_percent: float,
     *     categories: list<array{label: string, amount: float, share: float}>
     * }
     */
    public static function compute(
        float $budget,
        $startDate,
        $endDate,
        iterable $expenses,
        ?\DateTimeInterface $now = null,
    ): array {
        $asOf = $now
            ? \Carbon\Carbon::parse($now)->startOfDay()
            : \Carbon\Carbon::now()->startOfDay();
        $start = $startDate
            ? \Carbon\Carbon::parse($startDate)->startOfDay()
            : $asOf->copy();
        $elapsedDays = max(1, (int) abs($asOf->diffInDays($start)));

        $spent = 0.0;
        $byCategory = [];
        foreach ($expenses as $expense) {
            $amount = (float) (is_array($expense) ? ($expense['amount'] ?? 0) : ($expense->amount ?? 0));
            $spent += $amount;
            $raw = is_array($expense) ? ($expense['category'] ?? 'Other') : ($expense->category ?? 'Other');
            $label = Labels::expenseCategory((string) $raw);
            $byCategory[$label] = ($byCategory[$label] ?? 0) + $amount;
        }

        $remaining = $budget - $spent;
        $burnPerDay = $spent / $elapsedDays;
        $end = $endDate ? \Carbon\Carbon::parse($endDate)->startOfDay() : null;
        $plannedDays = $end ? max(1, (int) abs($end->diffInDays($start))) : $elapsedDays;
        $projected = $burnPerDay * $plannedDays;

        $runway = null;
        $overDate = null;
        if ($burnPerDay > 0) {
            if ($remaining > 0) {
                $runway = (int) floor($remaining / $burnPerDay);
                $overDate = $asOf->copy()->addDays($runway)->toDateString();
            } else {
                $runway = 0;
                $overDate = $asOf->toDateString();
            }
        }

        $willExceed = $budget > 0 && $projected > $budget + 0.009;
        $catTotal = max(0.0001, array_sum($byCategory));
        arsort($byCategory);
        $categories = [];
        foreach ($byCategory as $label => $amount) {
            $categories[] = [
                'label' => $label,
                'amount' => $amount,
                'share' => $amount / $catTotal,
            ];
        }

        return [
            'spent' => $spent,
            'budget' => $budget,
            'remaining' => $remaining,
            'elapsed_days' => $elapsedDays,
            'burn_per_day' => $burnPerDay,
            'projected_final_cost' => $projected,
            'runway_days' => $runway,
            'will_exceed' => $willExceed,
            'projected_over_date' => $willExceed ? $overDate : null,
            'used_percent' => $budget <= 0 ? 0.0 : min(999, ($spent / $budget) * 100),
            'projected_percent' => $budget <= 0 ? 0.0 : min(999, ($projected / $budget) * 100),
            'categories' => $categories,
        ];
    }
}
