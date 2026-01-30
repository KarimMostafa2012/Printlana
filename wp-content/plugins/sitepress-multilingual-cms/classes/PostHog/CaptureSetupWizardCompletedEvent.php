<?php

namespace WPML\PostHog\Event;

use WPML\Core\Component\PostHog\Application\Service\Config\ConfigService;
use WPML\Core\Component\PostHog\Application\Service\Event\CaptureEventService;
use WPML\Core\Component\PostHog\Domain\Event\EventInterface;
use WPML\Infrastructure\WordPress\Component\PostHog\Domain\Event\SetupWizard\Capture\CaptureWizardCompleted;

class CaptureSetupWizardCompletedEvent {

	public static function capture( EventInterface $event, $personProps = [] ) {
		$postHogConfig = ( new ConfigService() )->create();

		global $wpml_dic;

		/** @var CaptureWizardCompleted $postHogCaptureEvent */
		$postHogCaptureEvent = $wpml_dic->make( CaptureWizardCompleted::class );

		/** @var CaptureEventService $postHogCaptureEventService */
		$postHogCaptureEventService = $wpml_dic->make( CaptureEventService::class, [
			':captureEvent' => $postHogCaptureEvent,
		] );

		$postHogCaptureEventService->capture(
			$postHogConfig,
			$event,
			$personProps
		);
	}

}
