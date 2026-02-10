<?php

declare(strict_types=1);

namespace PaymentGateway\Traits;

/**
 * Trait for data encryption capabilities
 */
trait HasEncryption
{
    /**
     * Encryption key
     */
    protected string $encryptionKey = '';

    /**
     * Encryption cipher
     */
    protected string $cipher = 'AES-256-CBC';

    /**
     * Set encryption key
     */
    public function setEncryptionKey(string $key): void
    {
        $this->encryptionKey = $key;
    }

    /**
     * Encrypt data
     *
     * @param string $data Data to encrypt
     * @param string|null $key Optional encryption key
     *
     * @return string Encrypted data (base64 encoded)
     */
    public function encrypt(string $data, ?string $key = null): string
    {
        $key = $key ?? $this->encryptionKey;

        if (empty($key)) {
            throw new \RuntimeException('Encryption key is not set');
        }

        // Generate IV
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($this->cipher));

        // Encrypt
        $encrypted = openssl_encrypt($data, $this->cipher, $key, 0, $iv);

        // Combine IV and encrypted data
        return base64_encode($iv . $encrypted);
    }

    /**
     * Decrypt data
     *
     * @param string $data Encrypted data (base64 encoded)
     * @param string|null $key Optional encryption key
     *
     * @return string Decrypted data
     */
    public function decrypt(string $data, ?string $key = null): string
    {
        $key = $key ?? $this->encryptionKey;

        if (empty($key)) {
            throw new \RuntimeException('Encryption key is not set');
        }

        // Decode and extract IV
        $data = base64_decode($data, true);
        $ivLength = openssl_cipher_iv_length($this->cipher);
        $iv = substr($data, 0, $ivLength);
        $encrypted = substr($data, $ivLength);

        // Decrypt
        $decrypted = openssl_decrypt($encrypted, $this->cipher, $key, 0, $iv);

        if ($decrypted === false) {
            throw new \RuntimeException('Decryption failed');
        }

        return $decrypted;
    }

    /**
     * Hash data
     */
    public function hash(string $data, string $algo = 'sha256'): string
    {
        return hash($algo, $data);
    }

    /**
     * Generate HMAC signature
     */
    public function hmac(string $data, string $secret, string $algo = 'sha256'): string
    {
        return hash_hmac($algo, $data, $secret);
    }

    /**
     * Verify HMAC signature
     */
    public function verifyHmac(string $data, string $signature, string $secret, string $algo = 'sha256'): bool
    {
        $expectedSignature = $this->hmac($data, $secret, $algo);
        return hash_equals($expectedSignature, $signature);
    }
}
