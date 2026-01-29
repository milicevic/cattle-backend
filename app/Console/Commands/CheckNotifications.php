<?php

namespace App\Console\Commands;

use App\Models\Farm;
use App\Models\Farmer;
use App\Services\CattleService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:check {--farm-id= : Specific farm ID to check}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check and display notifications for upcoming calvings and insemination needs';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $cattleService = new CattleService();
        $farmId = $this->option('farm-id');

        if ($farmId) {
            $farm = Farm::find($farmId);
            if (!$farm) {
                $this->error("Farm with ID {$farmId} not found.");
                return 1;
            }
            $this->info("Checking notifications for farm: {$farm->name}");
            $notifications = $cattleService->getNotifications($farmId);
            $this->processNotifications($notifications, $farmId);
        } else {
            $this->info("Checking notifications for all farms...");
            
            // Get all farms and check notifications for each
            $farms = Farm::with('farmer')->get();
            $totalNotifications = 0;
            
            foreach ($farms as $farm) {
                $notifications = $cattleService->getNotifications($farm->id);
                if (!empty($notifications)) {
                    $this->info("\n=== Farm: {$farm->name} ===");
                    $this->processNotifications($notifications, $farm->id);
                    $totalNotifications += count($notifications);
                }
            }
            
            if ($totalNotifications === 0) {
                $this->info("No notifications at this time.");
            } else {
                $this->info("\nTotal notifications across all farms: {$totalNotifications}");
            }
        }

        return 0;
    }

    /**
     * Process and display notifications for a farm.
     */
    private function processNotifications(array $notifications, int $farmId): void
    {
        if (empty($notifications)) {
            return;
        }

        $this->info("Found " . count($notifications) . " notification(s):\n");

        $highPriority = array_filter($notifications, fn($n) => $n['priority'] === 'high');
        $mediumPriority = array_filter($notifications, fn($n) => $n['priority'] === 'medium');

        if (!empty($highPriority)) {
            $this->warn("HIGH PRIORITY (" . count($highPriority) . "):");
            foreach ($highPriority as $notification) {
                $this->line("  ⚠ " . $notification['message']);
            }
            $this->line("");
            
            // Log high priority notifications
            Log::warning("High priority notifications for farm {$farmId}", [
                'farm_id' => $farmId,
                'notifications' => $highPriority,
            ]);
        }

        if (!empty($mediumPriority)) {
            $this->info("MEDIUM PRIORITY (" . count($mediumPriority) . "):");
            foreach ($mediumPriority as $notification) {
                $this->line("  ℹ " . $notification['message']);
            }
            $this->line("");
        }

        // Summary table
        $this->table(
            ['Type', 'Tag Number', 'Priority', 'Message'],
            array_map(function ($notification) {
                return [
                    $notification['type'] === 'calving_due_soon' ? 'Calving' : 'Insemination',
                    $notification['tag_number'],
                    strtoupper($notification['priority']),
                    $notification['message'],
                ];
            }, $notifications)
        );
    }
}
