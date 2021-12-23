<?php
namespace Ostoandel\Hashing;

\App::uses('Security', 'Utility');

class CakeHasher implements \Illuminate\Contracts\Hashing\Hasher
{

    /**
     *
     * {@inheritDoc}
     * @see \Illuminate\Contracts\Hashing\Hasher::check()
     */
    public function check($value, $hashedValue, array $options = [])
    {
        return $this->make($value) === $hashedValue;
    }

    /**
     *
     * {@inheritDoc}
     * @see \Illuminate\Contracts\Hashing\Hasher::make()
     */
    public function make($value, array $options = [])
    {
        return \Security::hash($value, null, true);
    }

    /**
     *
     * {@inheritDoc}
     * @see \Illuminate\Contracts\Hashing\Hasher::info()
     */
    public function info($hashedValue)
    {
        return [];
    }

    /**
     *
     * {@inheritDoc}
     * @see \Illuminate\Contracts\Hashing\Hasher::needsRehash()
     */
    public function needsRehash($hashedValue, array $options = [])
    {
        return fasle;
    }

}