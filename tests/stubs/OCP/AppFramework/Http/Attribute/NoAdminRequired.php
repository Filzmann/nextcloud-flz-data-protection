<?php

declare(strict_types=1);

namespace OCP\AppFramework\Http\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
final class NoAdminRequired {
}
