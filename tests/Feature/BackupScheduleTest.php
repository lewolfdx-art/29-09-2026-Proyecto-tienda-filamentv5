<?php

namespace Tests\Feature;

use Illuminate\Console\Scheduling\Schedule;
use Tests\TestCase;

class BackupScheduleTest extends TestCase
{
    public function test_backup_notification_emails_are_valid(): void
    {
        $recipients = config('backup.notifications.mail.to');

        $this->assertIsArray($recipients);

        foreach ($recipients as $email) {
            $this->assertNotSame('', $email);
            $this->assertNotFalse(filter_var($email, FILTER_VALIDATE_EMAIL));
        }
    }

    public function test_backups_and_audit_cleanup_are_scheduled(): void
    {
        $commands = collect(app(Schedule::class)->events())
            ->map(fn ($event): string => (string) ($event->command ?? ''));

        $this->assertTrue($commands->contains(fn (string $command): bool => str_contains($command, 'backup:run')));
        $this->assertTrue($commands->contains(fn (string $command): bool => str_contains($command, 'backup:clean')));
        $this->assertTrue($commands->contains(fn (string $command): bool => str_contains($command, 'backup:monitor')));
        $this->assertTrue($commands->contains(fn (string $command): bool => str_contains($command, 'activitylog:clean')));
    }
}
