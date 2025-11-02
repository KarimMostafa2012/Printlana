<?php

namespace U2Code\OrderMessenger\Config;

use U2Code\OrderMessenger\Core\ServiceContainer;
use U2Code\OrderMessenger\Entity\MessageType;
class Config {
    public static function isShowReadMessageCheckMark() {
        $value = self::getFromSettings( 'show_read_message_for_customers', 'yes' );
        return self::returnValue( 'show_read_message_for_customers', 'yes' === $value );
    }

    public static function isDisableWooCommerceOrderNotesEmailsEnabled() {
        $data = self::getFromSettings( 'show_system_order_notes', array(
            'disable_system_notes_emails' => 'yes',
        ) );
        return self::returnValue( 'show_system_order_notes', 'yes' === $data['disable_system_notes_emails'] );
    }

    public static function getCurrentMessengerThemeColors() {
        try {
            $availableThemes = ServiceContainer::getInstance()->get( 'settings.colorThemeOption' )->getAvailableThemes();
            $currentTheme = self::getValue( 'color_theme' );
            if ( array_key_exists( $currentTheme, $availableThemes ) ) {
                $theme = $availableThemes[$currentTheme];
            } else {
                $theme = array_values( $availableThemes )[0];
            }
        } catch ( \Exception $e ) {
            return array();
        }
        return self::returnValue( 'currentMessengerThemeColors', $theme );
    }

    public static function isServiceNotesMessagesEnabled() {
        return self::getCheckboxValue( 'show_system_order_notes', 'yes' );
    }

    public static function getEnabledMessageTypes( $context = null ) {
        $types = array(MessageType::ADMIN, MessageType::CUSTOMER);
        if ( self::isOrderStatusChangingMessagesEnabled() ) {
            $types[] = MessageType::ORDER_STATUS;
        }
        if ( self::isServiceNotesMessagesEnabled() ) {
            $types[] = MessageType::SERVICE;
        }
        return self::returnValue( 'enabled_message_types', $types, array(
            'context' => $context,
        ) );
    }

    public static function isOrderStatusChangingMessagesEnabled() {
        $key = 'show_order_status_changing';
        return self::returnValue( $key, self::getFromSettings( $key, 'yes' ) === 'yes' );
    }

    public static function isShowMessengerLinkInOrderActions() {
        $key = 'show_messenger_link_as_order_action';
        return self::returnValue( $key, self::getFromSettings( $key, 'yes' ) === 'yes' );
    }

    public static function getPreloadMessagesCount() {
        $key = 'preloaded_messages_count';
        return self::returnValue( $key, self::getFromSettings( $key, 20 ) );
    }

    public static function getStoreSignature() {
        $key = 'store_signature';
        return self::returnValue( $key, self::getFromSettings( $key, '' ) );
    }

    /**
     * Get maximum allowed filesize
     *
     * @param  bool  $inBytes
     *
     * @return int
     */
    public static function getMaxFileSize( $inBytes = false ) {
        $key = 'allow_send_files';
        $data = self::getFromSettings( $key, array(
            'filesize' => 2,
        ) );
        if ( $inBytes ) {
            $data['filesize'] *= 1024 * 1024;
        }
        // todo: take into account server settings
        return self::returnValue( $key, $data['filesize'] );
    }

    public static function isFilesEnabled() {
        return false;
    }

    public static function getEnabledFileFormats() {
        $key = 'allow_send_files';
        $data = self::getFromSettings( $key, array(
            'files' => array(),
        ) );
        try {
            $allowedFiles = ServiceContainer::getInstance()->get( 'settings.allowSendingFilesOption' )->getAvailableFileTypes();
            $allowedFiles = array_filter( $allowedFiles, function ( $fileKey ) use($data) {
                return array_key_exists( $fileKey, $data['files'] );
            }, ARRAY_FILTER_USE_KEY );
            $data['files'] = $allowedFiles;
        } catch ( \Exception $e ) {
            $data['files'] = array();
        }
        return self::returnValue( $key, $data['files'] );
    }

    public static function ifPurchaseMessageEnabled() {
        return self::getCheckboxValue( 'purchasing_message', 'yes' );
    }

    public static function getPurchaseMessageSendTrigger() {
        return self::getValue( 'purchasing_message', 'send_on', 'order_created' );
    }

    public static function getAllowedRolesToManagerAdminMessenger() {
        return self::getValue( 'allowed_user_roles_to_manage_messenger', null, array('administrator', 'shop_manager') );
    }

    public static function getMessageTypesCustomerShouldBeNotified() {
        return apply_filters( 'order_messenger/config/message_types_customer_should_be_notified', array(MessageType::ADMIN, MessageType::SERVICE) );
    }

    public static function getMessageTypesAdminShouldBeNotified() {
        return apply_filters( 'order_messenger/config/message_types_admin_should_be_notified', array(MessageType::CUSTOMER) );
    }

    private static function getCheckboxValue( $key, $default ) {
        return self::getValue( $key, 'enabled', $default ) === 'yes';
    }

    private static function getValue( $key, $subKey = null, $default = null ) {
        if ( $subKey ) {
            $data = self::getFromSettings( $key, array(
                $subKey => $default,
            ) );
            return self::returnValue( $key, $data[$subKey] );
        }
        return self::returnValue( $key, self::getFromSettings( $key, $default ) );
    }

    private static function returnValue( $key, $value, $args = array() ) {
        return apply_filters( 'order_messenger/config/get_' . $key, $value, $args );
    }

    private static function getFromSettings( $key, $default ) {
        return ServiceContainer::getInstance()->getSettings()->get( $key, $default );
    }

}
