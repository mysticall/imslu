<?php

function init_mbstrings() {
	$res = true;
	$res &= mbstrings_available();
	ini_set('mbstring.internal_encoding', 'UTF-8');
	$res &= (ini_get('mbstring.internal_encoding') == 'UTF-8');
	ini_set('mbstring.detect_order', 'UTF-8, ISO-8859-1, JIS, SJIS');
	$res &= (ini_get('mbstring.detect_order') == 'UTF-8, ISO-8859-1, JIS, SJIS');
	if ($res) {
		define('MBSTRINGS_ENABLED', true);
	}
	return $res;
}

function mbstrings_available() {
	return function_exists('mb_strlen') && function_exists('mb_strtoupper') && function_exists('mb_strpos') && function_exists('mb_substr');
}

function locale_unix($language) {
	$postfixes = array(
		'',
		'.utf8',
		'.UTF-8',
		'.iso885915',
		'.ISO8859-1',
		'.ISO8859-2',
		'.ISO8859-4',
		'.ISO8859-5',
		'.ISO8859-15',
		'.ISO8859-13',
		'.CP1131',
		'.CP1251',
		'.CP1251',
		'.CP949',
		'.KOI8-U',
		'.US-ASCII',
		'.eucKR',
		'.eucJP',
		'.SJIS',
		'.GB18030',
		'.GB2312',
		'.GBK',
		'.eucCN',
		'.Big5HKSCS',
		'.Big5',
		'.armscii8',
		'.cp1251',
		'.eucjp',
		'.euckr',
		'.euctw',
		'.gb18030',
		'.gbk',
		'.koi8r',
		'.tcvn'
	);
	$result = array();
	foreach ($postfixes as $postfix) {
		$result[] = $language.$postfix;
	}
	return $result;
}

?>
