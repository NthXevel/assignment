<?php
// Author: Lee Kai Yi
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Models\Order;

class AutoApproveUrgentOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:auto-approve-urgent';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Auto-approve urgent orders that exceeded their SLA window';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $overdue = Order::query()
            ->where('priority', 'urgent')
            ->where('status', 'pending')
            ->whereNotNull('sla_due_at')
            ->where('sla_due_at', '<=', now())
            ->get();

        if ($overdue->isEmpty()) {
            $this->info('No overdue urgent orders.');
            return 0;
        }

        // Load existing cached messages (structured)
        $messages = Cache::get('orders.escalation_banner', []);

        // Main branch id
        $mainId = 1;

        foreach ($overdue as $order) {
            // Only flip if still pending (not approved/shipped/received)
            if (!in_array($order->status, ['approved', 'shipped', 'received', 'cancelled'], true)) {
                $order->update([
                    'status'      => 'approved',
                    'approved_at' => now(),
                    // audit trail without new columns:
                    'notes'       => trim(($order->notes ? $order->notes."\n" : '').'[AUTO-APPROVED by SLA at '.now()->toDateTimeString().']'),
                ]);

                // Escalation log
                Log::warning("Order {$order->id} ({$order->order_number}) auto-approved by SLA escalation.");
                // Targeted banner: only Main + Supplying see this
                $messages[] = [
                    'text'       => "Urgent order {$order->order_number} was auto-approved due to SLA timeout.",
                    'branch_ids' => array_values(array_filter([$mainId, $order->supplying_branch_id])),
                    'at'         => now()->toDateTimeString(),
                ];
            }
        }

        // Keep only the latest 50 messages, visible for 10 minutes
        $messages = array_slice($messages, -50);
        Cache::put('orders.escalation_banner', $messages, now()->addMinutes(10));

        $this->info("Auto-approved {$overdue->count()} urgent orders.");
        return 0;
    }
}
