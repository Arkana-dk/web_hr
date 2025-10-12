<?php

namespace App\Domain\WorkSchedule;

use Illuminate\Support\ServiceProvider;
use App\Domain\WorkSchedule\Contracts\WorkScheduleImporterContract;
use App\Domain\WorkSchedule\Services\WorkScheduleImporter;

class WorkScheduleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(WorkScheduleImporterContract::class, WorkScheduleImporter::class);
    }
}
