<?php

namespace App\Jobs;

use App\Enums\CallToActionType;
use App\Enums\OperatorType;
use App\Jobs\FBComponents\AdCreative;
use App\Jobs\FBComponents\UploadAdMaterial;
use App\Jobs\FBComponents\CreateAd;
use App\Models\AdLog;
use App\Models\Copywriting;
use App\Models\FbAccount;
use App\Models\FbAdAccount;
use App\Models\FbAdTemplate;
use App\Models\FbApiToken;
use App\Models\FbPage;
use App\Models\FbPageForm;
use App\Models\FbPixel;
use App\Models\Link;
use App\Models\Material;
use App\Utils\Telegram;
use Exception;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Process\Pipe;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Pipeline;

class FacebookCreateAdV2 implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    private $timeout = 360;
    private $fbAdAccountId;
    private $operatorType;
    private $operatorId;
    private $fbAdTemplateId;
    private $campaignId;
    private $adsetId;
    private $options;
    private AdLog $adLog;
    private $ad_source_id;
    private $require_pbia_id;

    /**
     * Create a new job instance.
     * $options 里面需要 materialId, pixel, fbPageId, postId, url_params,
     *         $copyWritingId - 可以为空, $linkId 可以为空,
     */
    public function __construct($fbAdAccountId, string $operatorType, $operatorId, $campaignId, $adsetId,
                                $adTemplateId, $options, AdLog $adLog, bool $require_pbia_id=false)
    {
        $this->fbAdAccountId = $fbAdAccountId;
        $this->operatorType = $operatorType;
        $this->operatorId = $operatorId;
        $this->fbAdTemplateId = $adTemplateId;
        $this->campaignId = $campaignId;
        $this->adsetId = $adsetId;
        $this->options = $options;
        $this->adLog = $adLog;
        $this->require_pbia_id = $require_pbia_id;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // 文档: https://developers.facebook.com/docs/marketing-apis/get-started/
        // precheck
        // unique creative, upload creative
        // create ads: adcreative and ad

        $context = [];

        if ($this->operatorType === OperatorType::FacebookUser->value) {
            $fbAccount = FbAccount::query()->where('id', $this->operatorId)->firstOrFail();
            $context['fbAccount'] = $fbAccount;
            $context['apiToken'] = null;
            $context['operatorType'] = OperatorType::FacebookUser->value;
        } elseif ($this->operatorType === OperatorType::BMUser->value) {
            $apiToken = FbApiToken::query()->where('active', true)
                ->where('id', $this->operatorId)
                ->firstOrFail();
            $context['fbAccount'] = null;
            $context['apiToken'] = $apiToken['token'];
            $context['operatorType'] = OperatorType::BMUser->value;
        }
        $context['operatorId'] = $this->operatorId;
        $context['requirePbiaId'] = $this->require_pbia_id;

        $fbAdAccount = FbAdAccount::query()->where('id', $this->fbAdAccountId)->firstOrFail();
        $context['fbAdAccountSourceId'] = $fbAdAccount->source_id;
        $materialIdOpt = $this->options['material_id'] ?? '';
        if ($materialIdOpt === '' && ! empty($this->options['material_id_list']) && is_array($this->options['material_id_list'])) {
            $first = reset($this->options['material_id_list']);
            if ($first !== false && $first !== null && $first !== '') {
                $materialIdOpt = (string) $first;
            }
        }
        $context['materialId'] = $materialIdOpt;

        $fbPage = FbPage::query()->where('id', $this->options['page_id'])->firstOrFail();
        $context['fbPageSourceId'] = $fbPage->source_id;

        $adTemplate = FbAdTemplate::query()->where('id', $this->fbAdTemplateId)->firstOrFail();
        $context['objective'] = $adTemplate['objective'];
        $context['conversion_location'] = $adTemplate['conversion_location'];
        $context['postId'] = $this->options['post_id'] ?? null;
        $context['productSetId'] = $this->options['product_set'] ?? null;

        $context['cta'] = $adTemplate['call_to_action'] ?? CallToActionType::LearnMore->value;
        $context['adName'] = $adTemplate['ad_name'];
        $context['urlTags'] = $adTemplate['url_params'] ?? '';
        $context['pixel_id'] = $this->options['pixel_id'] ?? '';

        if (isset($this->options['form_id']) && $this->options['form_id']) {
            $pageForm = FbPageForm::query()->where('id', $this->options['form_id'])->firstOrFail();
            $context['formSourceId'] = $pageForm['source_id'];
        } else {
            $context['formSourceId'] = '';
        }

        if (isset($this->options['link_id'])) {
            $link = Link::query()->where('id', $this->options['link_id'])->firstOrFail();
            $context['link'] = $link['link'];
        } else {
            $context['link'] = '';
        }

        if (isset($this->options['copywriting_id'])) {
            $copyWriting = Copywriting::query()->where('id', $this->options['copywriting_id'])->firstOrFail();
            $context['primary_text'] = $copyWriting['primary_text'];
            $context['description'] = $copyWriting['description'];
            $context['headline'] = $copyWriting['headline'];
        } else {
            $context['primary_text'] = '';
            $context['description'] = '';
            $context['headline'] = '';
        }

        $slot = $this->options['creative_asset_slot'] ?? null;
        if ($slot === null && ! empty($this->options['creative_asset_slots']) && is_array($this->options['creative_asset_slots']) && $context['materialId'] !== '') {
            foreach ($this->options['creative_asset_slots'] as $s) {
                if (is_array($s) && (string) ($s['material_id'] ?? '') === (string) $context['materialId']) {
                    $slot = $s;
                    break;
                }
            }
        }
        if (is_array($slot)) {
            if (($slot['body'] ?? '') !== '') {
                $context['primary_text'] = (string) $slot['body'];
            }
            if (($slot['headline'] ?? '') !== '') {
                $context['headline'] = (string) $slot['headline'];
            }
            if (($slot['description'] ?? '') !== '') {
                $context['description'] = (string) $slot['description'];
            }
            if (trim((string) ($slot['link'] ?? '')) !== '') {
                $context['link'] = trim((string) $slot['link']);
            }
            if (trim((string) ($slot['cta'] ?? '')) !== '') {
                $context['cta'] = trim((string) $slot['cta']);
            }
        }

        $context['adsetId'] = $this->adsetId;
        $context['random'] = $this->options['random'] ?? '';

        $context['adStatus'] = strtoupper((string) ($this->options['ad_status'] ?? 'PAUSED'));
        if (! in_array($context['adStatus'], ['ACTIVE', 'PAUSED'], true)) {
            $context['adStatus'] = 'PAUSED';
        }

        $context['trackingSpecsEncoded'] = null;
        $trackEvt = $this->options['website_tracking_event'] ?? null;
        $pixelOptId = $this->options['pixel_id'] ?? null;
        if ($trackEvt && $pixelOptId) {
            $pixelObj = FbPixel::query()->where('id', $pixelOptId)->first();
            if ($pixelObj) {
                $context['trackingSpecsEncoded'] = json_encode([
                    [
                        'action.type' => ['offsite_conversion'],
                        'fb_pixel' => [(string) $pixelObj->source_id],
                        'fb_event' => [(string) $trackEvt],
                    ],
                ]);
            }
        }

        Log::debug("create ad, context", $context);

        Pipeline::send($this)
            ->send($context)
            ->through([
                UploadAdMaterial::class,
                AdCreative::class,
                CreateAd::class
            ])->then(function ($context) {
                $creativeId = $context['creativeId'];
                $adSourceId = $context['adSourceId'];
                Log::debug("creativeId: {$creativeId}, adId: {$adSourceId}");

                $this->ad_source_id = $adSourceId;
                $this->adLog->ads()->syncWithoutDetaching([
                    $this->ad_source_id => [ 'ad_created' => true ]
                ]);
                $this->adLog->is_success = true;
                $this->adLog->save();

                // 同步 ad
//                $today = Carbon::now()->format('Y-m-d');
//                FacebookFetchAd::dispatch($this->fbAdAccountId, $today,
//                    $today, null, false, false, 1, [
//                        'field' => 'id',
//                        'operator' => 'IN',
//                        'value' => [$adSourceId]
//                    ])->onQueue('facebook');
                FacebookFetchAdV2::dispatch($this->fbAdAccountId, null, null, null, false,[
                    'field' => 'id',
                    'operator' => 'IN',
                    'value' => [$adSourceId]
                ])->onQueue('frontend');
            });
    }

    public function failed(\Throwable $exception)
    {
        // 处理失败逻辑
        // Log failure
        $msg = "Failed to create ad: {$this->operatorType} : {$this->operatorType}, camp id: {$this->operatorId}, adset id: {$this->adsetId}, log id: {$this->adLog->id}";

        if ($this->ad_source_id) {
            $this->adLog->ads()->syncWithoutDetaching([
                $this->ad_source_id => [
                    'adset_created' => false,
                    'adset_failed_reason' => $exception->getMessage()
                ]
            ]);
        } else {
            $this->adLog->is_success = false;
            $this->adLog->failed_reason = $exception->getMessage();
        }
        $this->adLog->save();

        Log::error('Failed to create adset: ' . $exception->getMessage());
        Telegram::sendMessage($msg);
    }
}
