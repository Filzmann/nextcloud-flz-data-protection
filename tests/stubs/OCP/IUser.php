<?php

declare(strict_types=1);

namespace OCP;

interface IUser {
    public function getUID(): string;
}
