<?php

namespace App\Providers;

use Illuminate\Console\Events\CommandStarting;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        JsonResource::withoutWrapping();

        if ($this->app->runningInConsole()) {
            $this->regenerateApiDocsOnServe();
        }
    }

    /**
     * Keep the exported OpenAPI spec (api.json) fresh whenever the dev
     * server starts, so it never needs a manual `scramble:export`.
     */
    private function regenerateApiDocsOnServe(): void
    {
        Event::listen(CommandStarting::class, function (CommandStarting $event): void {
            if (! in_array($event->command, ['serve', 'dev'], true)) {
                return;
            }

            try {
                Artisan::call('scramble:export', ['--no-interaction' => true]);
            } catch (\Throwable) {
                // Docs generation is a convenience; never block the dev server over it.
            }
        });
    }
}
