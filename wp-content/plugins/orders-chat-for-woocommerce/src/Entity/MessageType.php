<?php namespace U2Code\OrderMessenger\Entity;

class MessageType {

	const ADMIN = 0;
	const CUSTOMER = 1;
	const SERVICE = 2;
	const ORDER_STATUS = 3;

	private $type;

	/**
	 * MessageType constructor.
	 *
	 * @param $type
	 */
	private function __construct( $type ) {
		$this->type = $type;
	}

	/**
	 * Create from integer
	 *
	 * @param $type
	 *
	 * @return MessageType
	 */
	public static function fromInt( $type ) {
		if ( self::isValid( $type ) ) {
			return new self( $type );
		}

		return new self( self::SERVICE );
	}

	public function __toString() {
		return $this->getName();
	}

	public function getName() {
		return self::getValidTypes()[ $this->type ];
	}

	public function toInt() {
		return (int) $this->type;
	}

	public static function admin() {
		return new self( self::ADMIN );
	}

	public static function customer() {
		return new self( self::CUSTOMER );
	}

	public static function orderStatus() {
		return new self( self::ORDER_STATUS );
	}

	public static function service() {
		return new self( self::SERVICE );
	}

	public function isAdmin() {
		return $this->toInt() === self::ADMIN;
	}

	public function isCustomer() {
		return $this->toInt() === self::CUSTOMER;
	}

	public function isOrderStatus() {
		return $this->toInt() === self::ORDER_STATUS;
	}

	public function isService() {
		return $this->toInt() === self::SERVICE;
	}

	public static function getValidTypes() {
		return apply_filters( 'order_messenger/message/allowed_types', array(
			self::ADMIN        => 'admin',
			self::CUSTOMER     => 'customer',
			self::SERVICE      => 'service',
			self::ORDER_STATUS => 'order-status'
		) );
	}

	protected static function isValid( $type ) {
		return array_key_exists( $type, self::getValidTypes() );
	}
}
