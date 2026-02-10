<?php

declare(strict_types=1);

namespace PaymentGateway\Traits;

use Exception;

/**
 * Trait for retry logic capabilities
 */
trait HasRetry
{
    /**
     * Retry configuration
     */
    protected array $retryConfig = [
        'max_attempts' => 3,
        'delay' => 1000, // milliseconds
        'backoff_multiplier' => 2,
        'max_delay' => 30000,
    ];

    /**
     * Set retry configuration
     */
    public function setRetryConfig(array $config): void
    {
        $this->retryConfig = array_merge($this->retryConfig, $config);
    }

    /**
     * Execute operation with retry logic
     *
     * @param callable $operation Operation to retry
     * @param array $retryableExceptions Exception classes to retry on
     *
     * @return mixed Operation result
     *
     * @throws Exception Last exception if all retries fail
     */
    public function retry(callable $operation, array $retryableExceptions = []): mixed
    {
        $maxAttempts = $this->retryConfig['max_attempts'];
        $delay = $this->retryConfig['delay'];
        $attempt = 0;
        $lastException = null;

        while ($attempt < $maxAttempts) {
            try {
                return $operation();
            } catch (Exception $e) {
                $attempt++;
                $lastException = $e;

                // Check if we should retry
                if (!$this->shouldRetry($e, $retryableExceptions) || $attempt >= $maxAttempts) {
                    throw $e;
                }

                // Calculate backoff delay
                $backoffDelay = $delay * ($this->retryConfig['backoff_multiplier'] ** ($attempt - 1));
                $backoffDelay = min($backoffDelay, $this->retryConfig['max_delay']);

                // Sleep for calculated delay (convert ms to microseconds)
                usleep((int)($backoffDelay * 1000));
            }
        }

        throw $lastException ?? new Exception('Operation failed after retries');
    }

    /**
     * Determine if an exception should trigger a retry
     */
    protected function shouldRetry(Exception $exception, array $retryableExceptions): bool
    {
        // If specific retryable exceptions are defined, check against them
        if (!empty($retryableExceptions)) {
            foreach ($retryableExceptions as $class) {
                if ($exception instanceof $class) {
                    return true;
                }
            }
            return false;
        }

        // Default retryable exceptions
        $defaultRetryable = [
            'ConnectionException',
            'TimeoutException',
            'TooManyRequestsException',
        ];

        $exceptionClass = class_basename(get_class($exception));
        return in_array($exceptionClass, $defaultRetryable);
    }

    /**
     * Get current retry configuration
     */
    public function getRetryConfig(): array
    {
        return $this->retryConfig;
    }

    /**
     * Reset retry configuration
     */
    public function resetRetryConfig(): void
    {
        $this->retryConfig = [
            'max_attempts' => 3,
            'delay' => 1000,
            'backoff_multiplier' => 2,
            'max_delay' => 30000,
        ];
    }
}
