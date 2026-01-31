<?php
namespace WpAssetCleanUpPro;

/**
 *
 */
class MiscPro
{
    /**
     * @param $type
     * @param $typeId
     *
     * @return array|false|int|string|void|\WP_Error|\WP_Term|null
     */
    public static function getPageUrl($type, $typeId)
    {
        // Is there a type and type ID?
        if ($type && $typeId > 0) {
            return get_term_link($typeId, $type);
        }
    }

	/**
	 * When RegEx rules are updated from a textarea which allows one RegEx per line as well
	 * Filtering is applied to make sure the RegEx delimiters are added as well as the space is trimmed
	 * Do not leave any room for mistakes, especially trusting the user input as even involuntary mistakes often happen
	 *
	 * @param string $textareaValue
	 *
	 * @return string
	 */
	public static function purifyTextareaRegexValue($textareaValue)
	{
		$newTextareaValue = trim($textareaValue); // default

		try {
			if ( class_exists( '\CleanRegex\Pattern' )
			     && class_exists( '\SafeRegex\preg' )
			     && method_exists( '\CleanRegex\Pattern', 'delimitered' )
			     && method_exists( '\SafeRegex\preg', 'match' ) ) {
				$newTextareaValue = '';

				$regExes = array();

				if ( strpos( $textareaValue, "\n" ) !== false ) {
					foreach ( explode( "\n", $textareaValue ) as $regEx ) {
						$regExes[] = trim($regEx); // multiple RegExes added separated by a new line
					}
				} else {
					$regExes[] = $textareaValue; // just one RegEx added
				}

				$regExes = array_filter($regExes);

				foreach ( $regExes as $regEx ) {
					$cleanRegexPattern = new \CleanRegex\Pattern( $regEx );
					$delimiteredValue  = $cleanRegexPattern->delimitered(); // autocorrect it if there's no delimiter

					if ( $delimiteredValue ) {
						// Tip: https://stackoverflow.com/questions/4440626/how-can-i-validate-regex
						// Validate it and if it doesn't match, do not add it to the list
						@preg_match( $delimiteredValue, null );
						if ( preg_last_error() !== PREG_NO_ERROR ) {
							continue; // not valid, do not add it to the final list
						}

						$newTextareaValue .= $delimiteredValue."\n";
                    } else {
						$newTextareaValue .= $regEx."\n"; // hmm, something should have been returned, use the input value
					}
				}

				$newTextareaValue = trim($newTextareaValue);
			}
		} catch( \Exception $e) {} // if T-Regx library didn't load as it should, the textarea value will be kept as it is

		return $newTextareaValue;
	}

	/**
	 * @param $needles
	 * @param $haystack
	 *
	 * @return bool
	 */
	public static function inArrayIfAnyExists($needles, $haystack)
	{
		return ! empty(array_intersect($needles, $haystack));
	}

    /**
     * @param $strFind
     * @param $strReplaceWith
     * @param $string
     *
     * @return mixed
     */
    public static function strReplaceOnce($strFind, $strReplaceWith, $string)
    {
        if ( strpos($string, $strFind) === false ) {
            return $string;
        }

        $occurrence = strpos($string, $strFind);
        return substr_replace($string, $strReplaceWith, $occurrence, strlen($strFind));
    }
}
