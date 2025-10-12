<?php

namespace App\Domain\WorkSchedule\DTOs;

class WorkScheduleParseResult
{
    /** @var array<int,array> */
    public array $validSchedules = [];

    /** @var array<int,string> */
    public array $invalidEmployees = [];

    public function __construct(array $validSchedules = [], array $invalidEmployees = [])
    {
        $this->validSchedules = $validSchedules;
        $this->invalidEmployees = $invalidEmployees;
    }

    public function isEmpty(): bool
    {
        return count($this->validSchedules) === 0;
    }
}
