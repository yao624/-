<?php

namespace App\Console\Commands;

use App\Models\FbAdAccountInsight;
use App\Models\FbAdInsight;
use App\Models\FbAdsetInsight;
use App\Models\FbCampaignInsight;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MigrateActionData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:migrate-action-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'fill actions fields by existing data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Log::info("start migrate actions data");

        Log::info(" -- start process Ad Account Insights ---");
        $insights = FbAdAccountInsight::whereNotNull('actions')->get();
        foreach ($insights as $insight) {
            foreach ($insight->actions as $action) {
                if ($action['action_type'] == 'add_to_cart') {
                    $insight->add_to_cart = intval($action['value']);
                    $insight->save();
                }
                if ($action['action_type'] == 'purchase') {
                    $insight->purchase = intval($action['value']);
                    $insight->save();
                }
                if ($action['action_type'] == 'lead') {
                    $insight->lead = intval($action['value']);
                    $insight->save();
                }
                if ($action['action_type'] == 'comment') {
                    $insight->comment = intval($action['value']);
                    $insight->save();
                }
            }
        }
        $insights = FbAdAccountInsight::whereNotNull('cost_per_action_type')->get();
        foreach ($insights as $insight)
        {
            foreach ($insight->cost_per_action_type as $action) {
                if ($action['action_type'] == 'purchase') {
                    $insight->cost_per_purchase = floatval($action['value']);
                    $insight->save();
                }
                if ($action['action_type'] == 'lead') {
                    $insight->cost_per_lead = floatval($action['value']);
                    $insight->save();
                }
                if ($action['action_type'] == 'add_to_cart') {
                    $insight->cost_to_add_to_cart = floatval($action['value']);
                    $insight->save();
                }
            }
        }
        Log::info(" -- end process Ad Account Insights ---");

        Log::info(" -- start process Campaign Insights ---");
        $insights = FbCampaignInsight::whereNotNull('actions')->get();
        foreach ($insights as $insight) {
            foreach ($insight->actions as $action) {
                if ($action['action_type'] == 'add_to_cart') {
                    $insight->add_to_cart = intval($action['value']);
                    $insight->save();
                }
                if ($action['action_type'] == 'purchase') {
                    $insight->purchase = intval($action['value']);
                    $insight->save();
                }
                if ($action['action_type'] == 'lead') {
                    $insight->lead = intval($action['value']);
                    $insight->save();
                }
                if ($action['action_type'] == 'comment') {
                    $insight->comment = intval($action['value']);
                    $insight->save();
                }
            }
        }
        $insights = FbCampaignInsight::whereNotNull('cost_per_action_type')->get();
        foreach ($insights as $insight) {
            foreach ($insight->cost_per_action_type as $action) {
                if ($action['action_type'] == 'purchase') {
                    $insight->cost_per_purchase = floatval($action['value']);
                    $insight->save();
                }
                if ($action['action_type'] == 'lead') {
                    $insight->cost_per_lead = floatval($action['value']);
                    $insight->save();
                }
                if ($action['action_type'] == 'add_to_cart') {
                    $insight->cost_to_add_to_cart = floatval($action['value']);
                    $insight->save();
                }
            }
        }

        Log::info(" -- end process Campaign Insights ---");

        Log::info(" -- start process Adset Insights ---");
        $insights = FbAdsetInsight::whereNotNull('actions')->get();
        foreach ($insights as $insight) {
            foreach ($insight->actions as $action) {
                if ($action['action_type'] == 'add_to_cart') {
                    $insight->add_to_cart = intval($action['value']);
                    $insight->save();
                }
                if ($action['action_type'] == 'purchase') {
                    $insight->purchase = intval($action['value']);
                    $insight->save();
                }
                if ($action['action_type'] == 'lead') {
                    $insight->lead = intval($action['value']);
                    $insight->save();
                }
                if ($action['action_type'] == 'comment') {
                    $insight->comment = intval($action['value']);
                    $insight->save();
                }
            }
        }
        $insights = FbAdsetInsight::whereNotNull('cost_per_action_type')->get();
        foreach ($insights as $insight) {
            foreach ($insight->cost_per_action_type as $action) {
                if ($action['action_type'] == 'purchase') {
                    $insight->cost_per_purchase = floatval($action['value']);
                    $insight->save();
                }
                if ($action['action_type'] == 'lead') {
                    $insight->cost_per_lead = floatval($action['value']);
                    $insight->save();
                }
                if ($action['action_type'] == 'add_to_cart') {
                    $insight->cost_to_add_to_cart = floatval($action['value']);
                    $insight->save();
                }
            }
        }

        Log::info(" -- end process Adset Insights ---");

        Log::info(" -- start process Ad Insights ---");
        $insights = FbAdInsight::whereNotNull('actions')->get();
        foreach ($insights as $insight) {
            foreach ($insight->actions as $action) {
                if ($action['action_type'] == 'add_to_cart') {
                    $insight->add_to_cart = intval($action['value']);
                    $insight->save();
                }
                if ($action['action_type'] == 'purchase') {
                    $insight->purchase = intval($action['value']);
                    $insight->save();
                }
                if ($action['action_type'] == 'lead') {
                    $insight->lead = intval($action['value']);
                    $insight->save();
                }
                if ($action['action_type'] == 'comment') {
                    $insight->comment = intval($action['value']);
                    $insight->save();
                }
            }

        }
        $insights = FbAdInsight::whereNotNull('cost_per_action_type')->get();
        foreach ($insights as $insight) {
            foreach ($insight->cost_per_action_type as $action) {
                if ($action['action_type'] == 'purchase') {
                    $insight->cost_per_purchase = floatval($action['value']);
                    $insight->save();
                }
                if ($action['action_type'] == 'lead') {
                    $insight->cost_per_lead = floatval($action['value']);
                    $insight->save();
                }
                if ($action['action_type'] == 'add_to_cart') {
                    $insight->cost_to_add_to_cart = floatval($action['value']);
                    $insight->save();
                }
            }
        }
        Log::info(" -- end process Ad Insights ---");

    }
}
