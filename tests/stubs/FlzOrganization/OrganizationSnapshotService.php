<?php

declare(strict_types=1);

namespace OCA\FlzOrganization\PublicApi\V1;

final class OrganizationSnapshotService
{
    public bool $fail = false;

    public function __construct(public object $value)
    {
    }

    public function snapshot(): object
    {
        if ($this->fail)
        {
            throw new \RuntimeException('SYNTHETIC-SENSITIVE-PAYLOAD');
        }
        return $this->value;
    }
}
