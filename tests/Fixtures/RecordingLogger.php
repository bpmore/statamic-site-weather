<?php

declare(strict_types=1);

namespace Bpmore\SiteWeather\Tests\Fixtures;

use Psr\Log\AbstractLogger;
use Stringable;

/** Keeps every log record so a test can say exactly what was reported. */
final class RecordingLogger extends AbstractLogger
{
    /** @var list<array{level: string, message: string, context: array<string, mixed>}> */
    public array $records = [];

    public function log($level, string|Stringable $message, array $context = []): void
    {
        $this->records[] = ['level' => (string) $level, 'message' => (string) $message, 'context' => $context];
    }

    /** @return list<string> */
    public function messages(?string $level = null): array
    {
        return array_values(array_map(
            fn (array $record) => $record['message'],
            array_filter($this->records, fn (array $record) => $level === null || $record['level'] === $level),
        ));
    }
}
