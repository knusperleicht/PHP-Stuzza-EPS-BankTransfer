<?php
declare(strict_types=1);

namespace Knusperleicht\EpsBankTransfer\Requests\Parts;

use InvalidArgumentException;

/**
 * Value object representing obscurity (hash suffix) configuration.
 *
 * Both properties must be provided together: length (>=0) and seed (string when length>0).
 */
class ObscurityConfig
{
    /** @var int */
    private $length;
    /** @var string|null */
    private $seed;

    public function __construct(int $length, ?string $seed)
    {
        if ($length < 0) {
            throw new InvalidArgumentException('Obscurity length must be a non-negative integer.');
        }
        $this->length = $length;
        $this->seed = $seed;
    }

    public function getLength(): int
    {
        return $this->length;
    }

    public function getSeed(): ?string
    {
        return $this->seed;
    }
}
