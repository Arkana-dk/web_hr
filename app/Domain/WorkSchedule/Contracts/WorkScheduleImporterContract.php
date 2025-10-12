<?php

namespace App\Domain\WorkSchedule\Contracts;

use Illuminate\Support\Collection;
use App\Domain\WorkSchedule\DTOs\WorkScheduleParseResult;

interface WorkScheduleImporterContract
{
    public function parse(Collection $sheet): WorkScheduleParseResult;

    /** @return array{created:int,updated:int} */
    public function persist(array $validSchedules): array;
}
