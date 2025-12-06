<?php namespace U2Code\OrderMessenger\Entity;

class MessageAttachment {

	private $id;

	private $metadata;

	/**
	 * MessageAttachment constructor.
	 *
	 * @param $id
	 */
	public function __construct( $id ) {
		$this->id = $id;
	}

	public function isImage() {
		return wp_attachment_is_image( $this->getId() );
	}

	public function getImageSrc( $size = 'thumbnail' ) {
		$attachment = wp_get_attachment_image_src( $this->getId(), $size );

		return is_array( $attachment ) ? $attachment[0] : '#';
	}

	public function getId() {
		return $this->id;
	}

	public function getURL() {
		return wp_get_attachment_url( $this->getId() );
	}

	public function getName( $formatted = false ) {

		if ( $formatted ) {
			return $this->getName() . ' ' . $this->getSize();
		}

		return get_the_title( $this->getId() );
	}

	public function getSize() {
		$metadata = $this->getMetadata();

		return $metadata ? size_format( $metadata['sizes']['file'] ) : 0;
	}

	public function getMetadata() {
		if ( is_null( $this->metadata ) ) {
			$this->metadata = wp_get_attachment_metadata( $this->getId() );
		}

		return $this->metadata;
	}

	public function isValid() {
		$attachment = get_post( $this->getId() );

		$valid = $attachment instanceof \WP_Post &&  'attachment' === $attachment->post_type;

		return apply_filters( 'order_messenger/message/attachment/is_valid', $valid, $this );
	}
}
