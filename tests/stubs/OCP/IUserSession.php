<?php

declare(strict_types=1);

namespace OCP;

interface IUserSession {
    public function getUser(): ?IUser;
}
