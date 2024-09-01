<?php
namespace STS\core\Security;

class Hash
{
    private static $defaultOptions = [
        'cost' => 12, // cost standard pentru bcrypt
    ];
    
    private static $algorithm = PASSWORD_BCRYPT;

    /**
     * Hash a password or other sensitive data.
     *
     * @param string $data
     * @return string
     */
    public static function make(string $data, array $options = []): string
    {
        // Combină opțiunile implicite cu cele personalizate
        $options = array_merge(self::$defaultOptions, $options);
        
        return password_hash($data, self::$algorithm, $options);
    }

    /**
     * Verify if a given plain data matches a hashed value.
     *
     * @param string $data
     * @param string $hash
     * @return bool
     */
    public static function check(string $data, string $hash): bool
    {
        return password_verify($data, $hash);
    }

    /**
     * Check if a hashed value needs to be rehashed according to the options.
     *
     * @param string $hash
     * @param array $options
     * @return bool
     */
    public static function needsRehash(string $hash, array $options = []): bool
    {
        return password_needs_rehash($hash, PASSWORD_BCRYPT, $options);
    }
}