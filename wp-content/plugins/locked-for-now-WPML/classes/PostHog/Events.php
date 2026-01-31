<?php

namespace WPML\PostHog\Event;

use WPML\Core\Component\PostHog\Domain\Event\WpmlPostHogEvents;

class Events {

	public static function WPMLRootPageSaved() {
		return WpmlPostHogEvents::WPML_ROOT_PAGE_SAVED;
	}


	public static function WPMLSetLanguageUrlFormat() {
		return WpmlPostHogEvents::WPML_SET_LANGUAGE_URL_FORMAT;
	}


	public static function WPMLSetLanguageUrlFormatFailed() {
		return WpmlPostHogEvents::WPML_SET_LANGUAGE_URL_FORMAT_FAILED;
	}


	public static function WPMLEditLanguagesFormSubmitted() {
		return WpmlPostHogEvents::WPML_EDIT_LANGUAGES_FORM_SUBMITTED;
	}


	public static function WPMLFooterLanguageSwitcherToggled() {
		return WpmlPostHogEvents::WPML_FOOTER_LANGUAGE_SWITCHER_TOGGLED;
	}


	public static function WPMLTaxonomyHierarchySyncNoticeDisplayed() {
		return WpmlPostHogEvents::WPML_TAXONOMY_HIERARCHY_SYNC_NOTICE_DISPLAYED;
	}


	public static function WPMLTaxonomyHierarchySyncLinkClicked() {
		return WpmlPostHogEvents::WPML_TAXONOMY_HIERARCHY_SYNC_LINK_CLICKED;
	}


	public static function WPMLTaxonomyHierarchySyncCompleted() {
		return WpmlPostHogEvents::WPML_TAXONOMY_HIERARCHY_SYNC_COMPLETED;
	}


	public static function WPMLTaxonomyTermTranslationSaved() {
		return WpmlPostHogEvents::WPML_TAXONOMY_TERM_TRANSLATION_SAVED;
	}


	public static function WPMLTranslationEditorSwitched() {
		return WpmlPostHogEvents::WPML_TRANSLATION_EDITOR_SWITCHED;
	}


	public static function WPMLPostTypeUnlocked() {
		return WpmlPostHogEvents::WPML_POST_TYPE_UNLOCKED;
	}


	public static function WPMLTaxonomyUnlocked() {
		return WpmlPostHogEvents::WPML_TAXONOMY_UNLOCKED;
	}


	public static function WPMLATEForOldTranslationsEnabled() {
		return WpmlPostHogEvents::WPML_ATE_FOR_OLD_TRANSLATIONS_ENABLED;
	}


	public static function WPMLAutomaticTranslationSettingsSaved() {
		return WpmlPostHogEvents::WPML_AUTOMATIC_TRANSLATION_SETTINGS_SAVED;
	}

}
