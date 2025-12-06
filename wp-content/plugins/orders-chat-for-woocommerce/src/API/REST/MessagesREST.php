<?php namespace U2Code\OrderMessenger\API\REST;

use Exception;
use U2Code\OrderMessenger\Config\Config;
use U2Code\OrderMessenger\Core\ServiceContainerTrait;
use U2Code\OrderMessenger\Entity\Message;
use U2Code\OrderMessenger\Entity\MessageAttachment;
use U2Code\OrderMessenger\Entity\MessageType;
use U2Code\OrderMessenger\Services\FileUploader;
use U2Code\OrderMessenger\Settings\EmailNotificationsOption;
use WP_REST_Request;
use WP_REST_Response;
use WP_User;

class MessagesREST {

	const API_NAMESPACE = 'order-messenger/v1';

	use ServiceContainerTrait;

	/**
	 * Messages constructor.
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'init' ) );
	}

	/**
	 * Init REST API
	 */
	public function init() {

		register_rest_route( self::API_NAMESPACE, '/unread/(?P<id>\d+)', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'unread' ),
			'permission_callback' => array( $this, 'checkPermission' ),
		) );

		register_rest_route( self::API_NAMESPACE, '/delete/(?P<id>\d+)', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'delete' ),
			'permission_callback' => array( $this, 'checkPermission' ),
		) );

		register_rest_route( self::API_NAMESPACE, '/get/(?P<limit>\d+)/(?P<offset>\d+)', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'get' ),
			'permission_callback' => array( $this, 'checkPermission' ),
			'args'                => array(
				'offset'   => array(
					'type'     => 'integer',
					'required' => false,
					'default'  => 0,
				),
				'limit'    => array(
					'type'     => 'integer',
					'default'  => 10,
					'required' => false,
				),
				'order_id' => array(
					'type'     => 'integer',
					'required' => true,
				),
				'place'    => array(
					'type'     => 'string',
					'required' => true,
				),
				'is_html'  => array(
					'type'     => 'bool',
					'required' => true,
				),
			),
		) );

		register_rest_route( self::API_NAMESPACE, '/send', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'send' ),
			'permission_callback' => array( $this, 'checkPermission' ),
			'args'                => array(
				'order_id' => array(
					'type'     => 'integer',
					'required' => true,
				),
				'message'  => array(
					'type'     => 'string',
					'required' => true,
				),
				'file'     => array(
					'required' => false,
				),
			),
		) );

		register_rest_route( self::API_NAMESPACE, '/sendAdmin', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'sendAdmin' ),
			'permission_callback' => array( $this, 'checkPermission' ),
			'args'                => array(
				'order_id'      => array(
					'type'     => 'integer',
					'required' => true,
				),
				'message'       => array(
					'type'     => 'string',
					'required' => true,
				),
				'attachment_id' => array(
					'required' => false,
				),
			),
		) );
	}

	/**
	 * Handler for `/save` route
	 *
	 * @param  WP_REST_Request  $request
	 *
	 * @return WP_REST_Response
	 */
	public function send( WP_REST_Request $request ) {

		if ( ! wp_verify_nonce( wp_create_nonce( 'valid_nonce' ), 'valid_nonce' ) ) {
			return new WP_REST_Response( array(
				'error'   => __( 'Something went wrong. Please try again.', 'order-messenger-for-woocommerce' ),
				'success' => false,
			) );
		}

		$orderId    = (int) $request->get_param( 'order_id' );
		$message    = trim( substr( $request->get_param( 'message' ), 0, Message::MAX_LENGTH ) );
		$file       = isset( $_FILES['file'] ) ? $request->get_file_params()['file'] : false;
		$customData = $this->parseCustomData( $request->get_param( 'custom_data' ) );

		$order = wc_get_order( $orderId );

		if ( $order ) {
			$userId   = $order->get_customer_id();
			$senderId = get_current_user_id();

			$userCanSendMessage = apply_filters( 'order_messenger/permissions/userCanSendMessage',
				$order->get_customer_id() === $userId, $userId, $order, $message );

			if ( ! $userCanSendMessage ) {
				return new WP_REST_Response( array(
					'error'   => __( 'You don\'t have enough permission to send a message to this order.',
						'order-messenger-for-woocommerce' ),
					'success' => false,
				) );
			}

			$attachment = null;

			if ( $file && Config::isFilesEnabled() ) {
				try {
					$attachmentId = FileUploader::upload( $file, $orderId );
				} catch ( Exception $exception ) {
					return new WP_REST_Response( array(
						'error'   => $exception->getMessage(),
						'success' => false,
					) );
				}

				$attachment = new MessageAttachment( $attachmentId );
			}

			$message = new Message( trim( $message ), $orderId, $userId, $senderId, MessageType::customer(),
				$attachment );

			if ( ! EmailNotificationsOption::isNotificationsEnabled() ) {
				$message->setIsNotified( true );
			}

			try {
				$message->save();
			} catch ( Exception $e ) {
				return new WP_REST_Response( array(
					'error'   => __( 'Something went wrong. Please try again.', 'order-messenger-for-woocommerce' ),
					'success' => false,
				) );
			}

			return new WP_REST_Response( array(
				'success'     => true,
				'messageHTML' => $this->getContainer()->getFileManager()->renderTemplate( $message->getViewPath(),
					array( 'message' => $message ), true ),
			), 200 );

		} else {
			return new WP_REST_Response( array( 'error' => 'Order not found', 'success' => false ) );
		}
	}

	/**
	 * Handler for `/delete` route
	 *
	 * @param  WP_REST_Request  $request
	 *
	 * @return WP_REST_Response
	 * @throws Exception
	 */
	public function delete( WP_REST_Request $request ) {

		$messageId = (int) $request->get_param( 'id' );

		$message = $this->getContainer()->getMessageRepository()->getById( $messageId );

		if ( $message ) {

			$userCanDeleteMessage = apply_filters( 'order_messenger/permissions/userCanDeleteMessage',
				$this->userIsAdmin( get_current_user_id() ), $message );

			if ( ! $userCanDeleteMessage ) {
				return new WP_REST_Response( array(
					'error'   => __( 'You don\'t have enough permission to delete a message at this order.',
						'order-messenger-for-woocommerce' ),
					'success' => false,
				) );
			}

			try {
				$message->delete();
			} catch ( Exception $e ) {
				return new WP_REST_Response( array(
					'error'   => __( 'Something went wrong. Please try again.', 'order-messenger-for-woocommerce' ),
					'success' => false,
				) );
			}

			return new WP_REST_Response( array(
				'success' => true,
			), 200 );
		} else {
			return new WP_REST_Response( array( 'error' => 'Message not found', 'success' => false ) );
		}
	}

	/**
	 * Handler for `/unread` route
	 *
	 * @param  WP_REST_Request  $request
	 *
	 * @return WP_REST_Response
	 * @throws Exception
	 */
	public function unread( WP_REST_Request $request ) {

		$messageId = (int) $request->get_param( 'id' );

		$message = $this->getContainer()->getMessageRepository()->getById( $messageId );

		if ( $message ) {

			$userCanDeleteMessage = apply_filters( 'order_messenger/permissions/userCanUnreadMessage',
				$this->userIsAdmin( get_current_user_id() ), $message );

			if ( ! $userCanDeleteMessage ) {
				return new WP_REST_Response( array(
					'error'   => __( 'You don\'t have enough permission to manage messages.',
						'order-messenger-for-woocommerce' ),
					'success' => false,
				) );
			}

			try {
				$message->unread();
			} catch ( Exception $e ) {
				return new WP_REST_Response( array(
					'error'   => __( 'Something went wrong. Please try again.', 'order-messenger-for-woocommerce' ),
					'success' => false,
				) );
			}

			return new WP_REST_Response( array(
				'success' => true,
			), 200 );
		} else {
			return new WP_REST_Response( array( 'error' => 'Message not found', 'success' => false ) );
		}
	}

	/**
	 * Handler for `/saveAdmin` route
	 *
	 * @param  WP_REST_Request  $request
	 *
	 * @return WP_REST_Response
	 */
	public function sendAdmin( $request ) {

		$orderId      = (int) $request->get_param( 'order_id' );
		$attachmentId = (int) $request->get_param( 'attachment_id' );
		$message      = (string) substr( $request->get_param( 'message' ), 0, Message::MAX_LENGTH );
		$customData   = $this->parseCustomData( $request->get_param( 'custom_data' ) );

		$order = wc_get_order( $orderId );

		if ( $order ) {
			$userId   = $order->get_customer_id();
			$senderId = get_current_user_id();

			$userCanSendMessage = $this->userIsAdmin( $senderId );
			$userCanSendMessage = apply_filters( 'order_messenger/permissions/userCanSendAdminMessage',
				$userCanSendMessage, $userId, $order, $message, $attachmentId );

			if ( ! $userCanSendMessage ) {
				return new WP_REST_Response( array(
					'error'   => __( 'You don\'t have enough permission to send a message.',
						'order-messenger-for-woocommerce' ),
					'success' => false,
				) );
			}

			if ( $attachmentId && ! $this->isAttachment( $attachmentId ) ) {
				return new WP_REST_Response( array(
					'error'   => __( 'Attachment does not exists.', 'order-messenger-for-woocommerce' ),
					'success' => false,
				) );
			}

			$attachment = $attachmentId ? new MessageAttachment( $attachmentId ) : null;
			$message    = new Message( trim( $message ), $orderId, $userId, $senderId, MessageType::admin(),
				$attachment );

			if ( ! array_key_exists( 'order-messenger-custom-data[send_notification]',
					$customData ) || ! EmailNotificationsOption::isNotificationsEnabled() ) {
				$message->setIsNotified( true );
			}

			$message = apply_filters( 'order_messenger/messages/before_saving_admin_message', $message, $request,
				$customData, $order, $attachmentId );

			try {
				$message->save();

				// Make sure all messenger attachments is private.
				if ( $attachment ) {
					wp_update_post( array(
						'ID'          => $attachment->getId(),
						'post_status' => 'private',
					) );
				}

			} catch ( Exception $e ) {
				return new WP_REST_Response( array(
					'error'   => __( 'Something went wrong. Please try again.', 'order-messenger-for-woocommerce' ),
					'success' => false,
				) );
			}

			return new WP_REST_Response( array(
				'success'     => true,
				'messageHTML' => $this->getContainer()->getFileManager()->renderTemplate( $message->getViewPath( 'admin' ),
					array( 'message' => $message ), true ),
			), 200 );

		} else {
			return new WP_REST_Response( array( 'error' => 'Order not found', 'success' => false ) );
		}
	}


	/**
	 * Handler for `/get` route
	 *
	 * @param  WP_REST_Request  $request
	 *
	 * @return WP_REST_Response
	 */
	public function get( WP_REST_Request $request ) {

		$limit   = absint( $request->get_param( 'limit' ) );
		$offset  = absint( $request->get_param( 'offset' ) );
		$orderId = (int) $request->get_param( 'order_id' );
		$place   = (string) $request->get_param( 'place' );
		$isHTML  = (bool) $request->get_param( 'is_html' );

		$order = wc_get_order( $orderId );

		if ( $order ) {

			try {
				$messages = $this->getContainer()->getMessageRepository()->getForOrder( $orderId, $offset, $limit );

				if ( $isHTML ) {

					$place = 'admin' === $place ? $place : 'frontend';

					$messagesHTML = array_reduce( $messages,
						function ( $messagesHTML, Message $message ) use ( $place ) {

							$messagesHTML .= $this->getContainer()->getFileManager()->renderTemplate( $message->getViewPath( $place ),
								array(
									'message' => $message,
								), true );

							return $messagesHTML;

						}, '' );

					return new WP_REST_Response( array(
						'success'      => true,
						'messagesHTML' => $messagesHTML,
					), 200 );
				} else {
					$messages = array_map( function ( Message $message ) {
						return $message->getAsArray();
					}, $messages );

					return new WP_REST_Response( array(
						'success'  => true,
						'messages' => $messages,
					), 200 );
				}

			} catch ( Exception $e ) {
				return new WP_REST_Response( array( 'error' => 'Something went wrong.', 'success' => false ) );
			}

		} else {
			return new WP_REST_Response( array( 'error' => 'Order not found', 'success' => false ) );
		}
	}

	/**
	 * Check permission to access the API
	 *
	 * @return bool
	 */
	public function checkPermission() {
		return is_user_logged_in();
	}

	/**
	 * Check if user is admin
	 *
	 * @param  int  $userId
	 *
	 * @return bool
	 */
	protected function userIsAdmin( $userId ) {

		$user = new WP_User( $userId );

		$intersectRoles = array_intersect( $user->roles, Config::getAllowedRolesToManagerAdminMessenger() );

		return ! empty( $intersectRoles );
	}

	/**
	 * Check if id is an attachment
	 *
	 * @param  int  $attachmentId
	 *
	 * @return bool
	 */
	protected function isAttachment( $attachmentId ) {
		$post = get_post( $attachmentId );

		return $post && 'attachment' === $post->post_type;
	}

	protected function parseCustomData( $rawData ) {
		$data = array();

		if ( is_array( $rawData ) ) {
			foreach ( $rawData as $rawDataItem ) {
				if ( isset( $rawDataItem['name'] ) && isset( $rawDataItem['value'] ) ) {
					$data[ $rawDataItem['name'] ] = $rawDataItem['value'];
				}
			}
		}

		return $data;
	}
}
