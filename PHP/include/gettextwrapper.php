
<?php

/**
 * In case gettext functions do not exist, just replacing them with our own,
 * so user can see atleast english translation
 */
if (!function_exists('_')) {
	function _($string) {
		return $string;
	}
}

if (!function_exists('gettext')) {
	function gettext($string) {
		return $string;
	}
}

if (!function_exists('ngettext')) {
	function ngettext($string1, $string2, $n) {
		return $n == 1 ? $string1 : $string2;
	}
}

if (!function_exists('pgettext')) {
	function pgettext($context, $msgid) {
		$contextString = $context."\004".$msgid;
		$translation = _($contextString);
		return ($translation == $contextString) ? $msgid : $translation;
	}
}

if (!function_exists('npgettext')) {
	function npgettext($context, $msgid, $msgid_plural, $num) {
		$contextString = $context."\004".$msgid;
		$contextStringp = $context."\004".$msgid_plural;
		$translation = ngettext($contextString, $contextStringp, $num);
		if ($translation == $contextString || $translation == $contextStringp) {
			return $msgid;
		}
		else {
			return $translation;
		}
	}
}

function _s($string) {
	$arguments = array_slice(func_get_args(), 1);
	return vsprintf(_($string), $arguments);
}

function _n($string1, $string2, $value) {
	$arguments = array_slice(func_get_args(), 2);
	return vsprintf(ngettext($string1, $string2, $value), $arguments);
}

/**
 * Translates the string with respect to the given context and replaces placeholders with supplied arguments.
 * If no translation is found, the original string will be used. Unlimited number of parameters supplied.
 *
 * Example: _x('Message for arg1 "%1$s" and arg2 "%2$s"', 'context', 'arg1Value', 'arg2Value');
 * returns: 'Message for arg1 "arg1Value" and arg2 "arg2Value"'
 *
 * @param string $message   String to translate
 * @param string $context   Context of the string
 *
 * @return string
 */
function _x($message, $context) {
	$arguments = array_slice(func_get_args(), 2);

	if ($context == '') {
		return vsprintf($message, $arguments);
	}
	else {
		return vsprintf(pgettext($context, $message), $arguments);
	}
}

/**
 * Translates the string with respect to the given context and plural forms, also replaces placeholders with supplied arguments.
 * If no translation is found, the original string will be used. Unlimited number of parameters supplied.
 *
 * Example: _xn('%1$s message for arg1 "%2$s"', '%1$s messages for arg1 "%2$s"', 3, 'context', 'arg1Value');
 * returns: '3 messagges for arg1 "arg1Value"'
 *
 * @param string $message          string to translate
 * @param string $message_plural   string to translate for plural form
 * @param int    $num              number to determine usage of plural form, also is used as first replace argument
 * @param string $context          context of the string
 *
 * @return string
 */
function _xn($message, $message_plural, $num, $context) {
	$arguments = array_slice(func_get_args(), 4);
	array_unshift($arguments, $num);

	if ($context == '') {
		return vsprintf(ngettext($message, $message_plural, $num), $arguments);
	}
	else {
		return vsprintf(npgettext($context, $message, $message_plural, $num), $arguments);
	}
}

?>