<?php

declare(strict_types=1);

use OCA\FilzmannDataProtection\Controller\RetentionReviewController;
use OCA\FilzmannDataProtection\PublicApi\V1\RegisterRetentionProvidersEvent;
use OCA\FilzmannDataProtection\PublicApi\V1\RetentionCandidate;
use OCA\FilzmannDataProtection\PublicApi\V1\RetentionPolicy;
use OCA\FilzmannDataProtection\PublicApi\V1\RetentionPreviewPage;
use OCA\FilzmannDataProtection\PublicApi\V1\RetentionPreviewRequest;
use OCA\FilzmannDataProtection\PublicApi\V1\RetentionProvider;
use OCA\FilzmannDataProtection\PublicApi\V1\RetentionProviderDescriptor;
use OCA\FilzmannDataProtection\Service\RetentionAccessService;
use OCA\FilzmannDataProtection\Service\RetentionPreviewAggregator;
use OCA\FilzmannDataProtection\Service\RetentionSettingsService;
use OCP\AppFramework\Http\Http;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IAppConfig;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;

$assertSame = static function (mixed $expected, mixed $actual, string $message): void {
    if ($expected !== $actual) throw new RuntimeException($message);
};

$store = new class implements IAppConfig {
    public function getValueArray(string $appId, string $key, array $default = [], bool $lazy = false): array { return $default; }
    public function getValueBool(string $appId, string $key, bool $default = false, bool $lazy = false): bool { return $default; }
    public function setValueArray(string $appId, string $key, array $value, bool $lazy = false): void {}
    public function setValueBool(string $appId, string $key, bool $value, bool $lazy = false): void {}
};
$groups = new class implements IGroupManager {
    public function isAdmin(string $uid): bool { return $uid === 'admin-user'; }
    public function isInGroup(string $uid, string $gid): bool { return false; }
    public function groupExists(string $gid): bool { return true; }
};
$sessionFor = static fn(string $uid): IUserSession => new class($uid) implements IUserSession {
    public function __construct(private string $uid) {}
    public function getUser(): ?IUser { return new class($this->uid) implements IUser {
        public function __construct(private string $uid) {}
        public function getUID(): string { return $this->uid; }
    }; }
};
$provider = new class implements RetentionProvider {
    public function descriptor(): RetentionProviderDescriptor { return new RetentionProviderDescriptor('review_app', 'Review App', '1.0', 20); }
    public function policies(): array { return [new RetentionPolicy('record-review', 'Metadaten', 'Prüfung', 'CREATED_AT', 180, 'REVIEW', '1.0')]; }
    public function preview(RetentionPreviewRequest $request): RetentionPreviewPage {
        return new RetentionPreviewPage('complete', [new RetentionCandidate('record-review', 'review:1', '2026-01-01T00:00:00+00:00', 'REVIEW', 'Frist überschritten.')]);
    }
};
$dispatches = 0;
$events = new class($provider, $dispatches) implements IEventDispatcher {
    public function __construct(private RetentionProvider $provider, private int &$dispatches) {}
    public function dispatchTyped(Event $event): Event {
        $this->dispatches++;
        if ($event instanceof RegisterRetentionProvidersEvent) $event->register($this->provider);
        return $event;
    }
};
$request = new class implements IRequest {};
$settings = new RetentionSettingsService($store, $groups);
$aggregator = new RetentionPreviewAggregator($events);

$denied = new RetentionReviewController($request, new RetentionAccessService($sessionFor('ordinary-user'), $groups, $settings), $aggregator);
$assertSame(Http::STATUS_FORBIDDEN, $denied->report()->getStatus(), 'Ein unberechtigtes Konto erhielt die REVIEW-Vorschau.');
$assertSame(0, $dispatches, 'Ein verweigerter REVIEW-Aufruf hat Provider entdeckt.');

$allowed = new RetentionReviewController($request, new RetentionAccessService($sessionFor('admin-user'), $groups, $settings), $aggregator);
$response = $allowed->report();
$assertSame(Http::STATUS_OK, $response->getStatus(), 'Der anfängliche Adminzugriff wurde nicht erlaubt.');
$assertSame(1, $dispatches, 'Der erlaubte REVIEW-Aufruf muss genau eine Discovery auslösen.');
$assertSame('REVIEW', $response->getData()['providers']['review_app']['candidates'][0]['action'] ?? null, 'Die API liefert keinen REVIEW-Kandidaten.');
$assertSame(0, (new ReflectionMethod($allowed, 'report'))->getNumberOfParameters(), 'Die REVIEW-API akzeptiert einen frei übermittelten Bewertungszeitpunkt.');

echo "Retention review controller tests passed.\n";
