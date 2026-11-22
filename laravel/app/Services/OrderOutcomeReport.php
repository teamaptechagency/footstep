<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Cache;
class OrderOutcomeReport
{
    public static function fromStatuses($rows)
    {
        $totals = ['delivered' => 0, 'cancelled' => 0, 'other' => 0];
        foreach ($rows as $row) {
            $status = is_array($row) ? $row['status'] ?? '' : $row->status ?? '';
            $count = max(0, (int) (is_array($row) ? $row['total'] ?? 0 : $row->total ?? 0));
            $key = in_array($status, ['delivered', 'cancelled'], true) ? $status : 'other';
            $totals[$key] += $count;
        }
        $totals['finalized'] = $totals['delivered'] + $totals['cancelled'];
        $totals['total'] = $totals['finalized'] + $totals['other'];
        $totals['success_rate'] = $totals['finalized'] ? round(100 * $totals['delivered'] / $totals['finalized'], 2) : null;
        $totals['cancel_rate'] = $totals['success_rate'] === null ? null : round(100 - $totals['success_rate'], 2);
        return $totals;
    }
    public static function report($period = 'day')
    {
        $window = StoreAnalytics::window($period);
        $key = 'order_outcomes_v2:' . $window['period'] . ':' . $window['local_start']->format('Y-m-d');
        $outcome = Cache::remember($key, 30, function () use($window) {
            $rows = OrderTime::constrain(Order::query(), $window['local_start'], $window['local_end'])->selectRaw('delivery_status AS status, COUNT(*) AS total')->groupBy('delivery_status')->get();
            return self::fromStatuses($rows);
        });
        return ['window' => $window, 'outcome' => $outcome];
    }
}
