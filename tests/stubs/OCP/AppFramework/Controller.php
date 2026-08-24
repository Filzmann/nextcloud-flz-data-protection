<?php

declare(strict_types=1);

namespace OCP\AppFramework;

use OCP\IRequest;

class Controller {
    public function __construct(protected string $appName, protected IRequest $request) {
    }
}
