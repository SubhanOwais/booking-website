<?php

namespace App\Auth;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable as UserContract;

class PlaintextUserProvider extends EloquentUserProvider
{
    /**
     * Validate a user against the given credentials.
     *
     * This handles plain-text passwords from the Password column in your database.
     */
    public function validateCredentials(UserContract $user, array $credentials): bool
    {
        $plain = (string) ($credentials['password'] ?? '');
        $stored = (string) $user->getAuthPassword();

        // Direct plain-text comparison for your database
        // Your getAuthPassword() returns the Password column value
        return $plain !== '' && $stored !== '' && $plain === $stored;
    }

    /**
     * Retrieve a user by the given credentials.
     *
     * @param  array  $credentials
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveByCredentials(array $credentials)
    {
        if (empty($credentials) ||
           (count($credentials) === 1 &&
            array_key_exists('password', $credentials))) {
            return null;
        }

        // Build query to find user by Phone column
        $query = $this->newModelQuery();

        foreach ($credentials as $key => $value) {
            if ($key === 'password') {
                continue;
            }

            if (is_array($value) || $value instanceof \Closure) {
                $query->where($key, $value);
            } else {
                $query->where($key, $value);
            }
        }

        return $query->first();
    }
}
