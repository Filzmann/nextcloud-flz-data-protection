<?php

declare(strict_types=1);

namespace OCA\FlzDataProtection\Privacy;

use OCA\FlzDataProtection\PublicApi\V2\RegisterRetentionExecutionProvidersEvent;
use OCP\AppFramework\IAppContainer;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

final class AdminHistoryRetentionExecutionProviderListener implements IEventListener {public function __construct(private IAppContainer$container){}public function handle(Event$event):void{if($event instanceof RegisterRetentionExecutionProvidersEvent)$event->register($this->container->get(AdminHistoryRetentionExecutionProvider::class));}}
