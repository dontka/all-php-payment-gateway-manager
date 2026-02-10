<?php

declare(strict_types=1);

namespace PaymentGateway\Traits;

use DateTimeImmutable;

/**
 * Trait for logging capabilities
 */
trait HasLogging
{
    /**
     * Log levels
     */
    public const LOG_DEBUG = 'debug';
    public const LOG_INFO = 'info';
    public const LOG_WARNING = 'warning';
    public const LOG_ERROR = 'error';
    public const LOG_CRITICAL = 'critical';

    /**
     * Log storage
     */
    protected array $logs = [];

    /**
     * Log configuration
     */
    protected array $logConfig = [
        'enabled' => true,
        'level' => self::LOG_DEBUG,
        'max_entries' => 1000,
    ];

    /**
     * Log a message
     *
     * @param string $level Log level
     * @param string $message Log message
     * @param array $context Additional context
     */
    public function log(string $level, string $message, array $context = []): void
    {
        if (!$this->logConfig['enabled']) {
            return;
        }

        $entry = [
            'timestamp' => (new DateTimeImmutable())->format('Y-m-d H:i:s.u'),
            'level' => $level,
            'message' => $message,
            'context' => $context,
        ];

        $this->logs[] = $entry;

        // Maintain max log entries
        if (count($this->logs) > $this->logConfig['max_entries']) {
            array_shift($this->logs);
        }
    }

    /**
     * Log debug message
     */
    public function logDebug(string $message, array $context = []): void
    {
        $this->log(self::LOG_DEBUG, $message, $context);
    }

    /**
     * Log info message
     */
    public function logInfo(string $message, array $context = []): void
    {
        $this->log(self::LOG_INFO, $message, $context);
    }

    /**
     * Log warning message
     */
    public function logWarning(string $message, array $context = []): void
    {
        $this->log(self::LOG_WARNING, $message, $context);
    }

    /**
     * Log error message
     */
    public function logError(string $message, array $context = []): void
    {
        $this->log(self::LOG_ERROR, $message, $context);
    }

    /**
     * Log critical message
     */
    public function logCritical(string $message, array $context = []): void
    {
        $this->log(self::LOG_CRITICAL, $message, $context);
    }

    /**
     * Get all logs
     */
    public function getLogs(): array
    {
        return $this->logs;
    }

    /**
     * Get logs by level
     */
    public function getLogsByLevel(string $level): array
    {
        return array_filter($this->logs, fn ($entry) => $entry['level'] === $level);
    }

    /**
     * Clear logs
     */
    public function clearLogs(): void
    {
        $this->logs = [];
    }

    /**
     * Format logs for display
     */
    public function formatLogs(?string $level = null): string
    {
        $logs = $level ? $this->getLogsByLevel($level) : $this->logs;
        $output = '';

        foreach ($logs as $entry) {
            $output .= sprintf(
                "[%s] %s: %s%s\n",
                $entry['timestamp'],
                strtoupper($entry['level']),
                $entry['message'],
                !empty($entry['context']) ? ' ' . json_encode($entry['context']) : ''
            );
        }

        return $output;
    }

    /**
     * Set log level
     */
    public function setLogLevel(string $level): void
    {
        $this->logConfig['level'] = $level;
    }

    /**
     * Enable logging
     */
    public function enableLogging(): void
    {
        $this->logConfig['enabled'] = true;
    }

    /**
     * Disable logging
     */
    public function disableLogging(): void
    {
        $this->logConfig['enabled'] = false;
    }

    /**
     * Check if logging is enabled
     */
    public function isLoggingEnabled(): bool
    {
        return $this->logConfig['enabled'];
    }
}
