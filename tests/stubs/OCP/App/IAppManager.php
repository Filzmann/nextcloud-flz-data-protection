<?php

declare(strict_types=1);

namespace OCP\App;

interface IAppManager
{
    public function isInstalled($appId);
}
