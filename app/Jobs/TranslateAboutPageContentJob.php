<?php

namespace App\Jobs;

use App\Models\AboutPage;
use App\Services\Translation\AboutPageContentTranslationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Log;

class TranslateAboutPageContentJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var array<int> */
    public array $backoff = [10, 30, 60];

    public int $timeout = 120;

    /**
     * @param  array<string, mixed>  $sourceContent
     */
    public function __construct(
        public readonly int $aboutPageId,
        public readonly array $sourceContent,
        public readonly ?string $sourceLocale = null,
        public ?string $modelUpdatedAtSnapshot = null,
    ) {
        $this->onQueue(config('translation.queue.name', 'translations'));

        $connection = config('translation.queue.connection');

        if (filled($connection) && array_key_exists($connection, config('queue.connections', []))) {
            $this->onConnection($connection);
        }
    }

    public function handle(AboutPageContentTranslationService $service): void
    {
        $page = AboutPage::query()->find($this->aboutPageId);

        if ($page === null) {
            Log::warning('TranslateAboutPageContentJob: about page not found.', [
                'about_page_id' => $this->aboutPageId,
            ]);

            return;
        }

        $service->handle(
            page: $page,
            sourceContent: $this->sourceContent,
            sourceLocale: $this->sourceLocale,
            modelUpdatedAtSnapshot: $this->modelUpdatedAtSnapshot,
        );
    }

    /** @return array<int, string> */
    public function tags(): array
    {
        return [
            'translation',
            'AboutPage',
            'id:'.$this->aboutPageId,
        ];
    }

    /** @return array<int, object> */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping('google-translate'))
                ->releaseAfter(30)
                ->expireAfter(180),
        ];
    }
}
