<?php

namespace App\Support;

final class Labels
{
    public static function money(float|int|null $amount, string $symbol = 'KSh'): string
    {
        return $symbol.' '.number_format((float) $amount, 2, '.', ',');
    }

    public static function projectStatus(string $status): string
    {
        return $status;
    }

    public static function expenseStatus(string $status): string
    {
        return match ($status) {
            'pending' => 'Pending',
            'verified' => 'Verified',
            'rejected' => 'Rejected',
            'missing' => 'Missing Receipt',
            'logged' => 'Logged',
            'pending_review' => 'Needs Review',
            default => ucfirst(str_replace('_', ' ', $status)),
        };
    }

    public static function expenseCategory(string $category): string
    {
        return match (strtolower($category)) {
            'materials' => 'Materials',
            'labor', 'labour' => 'Labor',
            'equipment' => 'Equipment',
            'travel' => 'Travel',
            'permits' => 'Permits & Fees',
            'supplies' => 'Office Supplies',
            'marketing' => 'Marketing',
            'receipt' => 'Receipt / OCR',
            'other' => 'Other',
            default => ucfirst($category),
        };
    }

    public static function taskStatus(string $status): string
    {
        return match ($status) {
            'pending' => 'Pending',
            'in_progress' => 'In Progress',
            'done' => 'Done',
            'blocked' => 'Blocked',
            default => ucfirst(str_replace('_', ' ', $status)),
        };
    }

    public static function taskPriority(string $priority): string
    {
        return match ($priority) {
            'low' => 'Low',
            'medium' => 'Medium',
            'high' => 'High',
            default => ucfirst($priority),
        };
    }

    public static function activityType(string $type): string
    {
        return match ($type) {
            'labour', 'labor' => 'Labour',
            'site' => 'Site work',
            'delivery' => 'Delivery',
            'inspection' => 'Inspection',
            'other' => 'Other',
            default => ucfirst($type),
        };
    }

    public static function milestoneStatus(string $status): string
    {
        return match ($status) {
            'pending' => 'Pending',
            'done' => 'Done',
            'skipped' => 'Skipped',
            default => ucfirst($status),
        };
    }

    public static function statusTone(string $domain, string $value): string
    {
        $v = strtolower($value);

        return match ($domain) {
            'project' => match ($v) {
                'active' => 'ok',
                'planning' => 'info',
                'on hold' => 'warn',
                'done' => 'muted',
                default => 'info',
            },
            'expense' => match ($v) {
                'verified', 'logged' => 'ok',
                'pending', 'pending_review' => 'warn',
                'rejected', 'missing' => 'danger',
                default => 'info',
            },
            'task' => match ($v) {
                'done' => 'ok',
                'in_progress' => 'info',
                'blocked' => 'danger',
                'pending' => 'warn',
                default => 'info',
            },
            'priority' => match ($v) {
                'high' => 'danger',
                'medium' => 'warn',
                'low' => 'muted',
                default => 'info',
            },
            'milestone' => match ($v) {
                'done' => 'ok',
                'skipped' => 'muted',
                'pending' => 'warn',
                default => 'info',
            },
            default => 'info',
        };
    }

    public static function budgetUsedPercent(float $budget, float $spent): float
    {
        if ($budget <= 0) {
            return 0;
        }

        return min(100, ($spent / $budget) * 100);
    }

    /** Prefer a publicly fetchable receipt URL for web display. */
    public static function receiptUrl(?string $receiptImage, ?string $receiptRowUrl = null): ?string
    {
        foreach ([$receiptRowUrl, $receiptImage] as $candidate) {
            if (! is_string($candidate) || $candidate === '') {
                continue;
            }
            if (str_starts_with($candidate, 'http://') || str_starts_with($candidate, 'https://')) {
                return $candidate;
            }
            if (str_starts_with($candidate, '/storage/')) {
                return $candidate;
            }
        }

        return null;
    }

    public static function hasLocalOnlyReceipt(?string $receiptImage, $receipts = null): bool
    {
        if (self::receiptUrl($receiptImage, null) !== null) {
            return false;
        }
        if (is_iterable($receipts)) {
            foreach ($receipts as $r) {
                $u = is_object($r) ? ($r->image_url ?? null) : null;
                if (self::receiptUrl(null, is_string($u) ? $u : null) !== null) {
                    return false;
                }
            }
        }

        return is_string($receiptImage) && $receiptImage !== '';
    }
}
