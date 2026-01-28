<?php

namespace WPML\Compatibility\Divi\V5;

use WPML\LIB\WP\Hooks;
use WPML\PB\Integrations\Divi\Helper;
use WPML\FP\Str;

use function WPML\FP\spreadArgs;

class DynamicContent implements \IWPML_Frontend_Action, \IWPML_Backend_Action {

	public function add_hooks() {
		Hooks::onFilter( 'wpml_pb_register_strings_in_content', 10, 3 )
			->then( spreadArgs( [ $this, 'dontRegisterDynamicContent' ] ) );
	}

	/**
	 * If we find dynamic content, we take over the registration of the string and skip it.
	 *
	 * @param bool   $takeOver
	 * @param int    $postId
	 * @param string $content
	 *
	 * @return bool
	 */
	public function dontRegisterDynamicContent( $takeOver, $postId, $content ) {
		if ( ! $takeOver && Helper::isPostUsingDivi5( $postId ) && $this->isDynamicContent( $content ) ) {
			$takeOver = true;
		}

		return $takeOver;
	}

	/**
	 * @param string $content
	 *
	 * @return bool
	 */
	private function isDynamicContent( $content ) {
		return (bool) Str::match( '/^\$variable\(([^)$]+)\)\$/', $content );
	}
}
