<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Employee;

class AuditUserEmployeeLinks extends Command
{
    protected $signature = 'audit:user-employee-links';
    protected $description = 'Audit and report user <-> employee link consistency';

    public function handle(): int
    {
        $brokenUsers = User::doesntHave('employee')->get();
        $brokenEmployees = Employee::whereNull('user_id')->get();

        $this->info("==== Audit Result ====");
        $this->info("Users WITHOUT employees: " . $brokenUsers->count());
        foreach ($brokenUsers as $user) {
            $this->line("- User #{$user->id}: {$user->name} ({$user->email})");
        }

        $this->info("\nEmployees WITHOUT linked user: " . $brokenEmployees->count());
        foreach ($brokenEmployees as $emp) {
            $this->line("- Employee #{$emp->id}: {$emp->name} (no user_id)");
        }

        $this->info("\nAudit complete.");
        return Command::SUCCESS;
    }
}
