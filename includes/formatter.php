<?php
interface MinichanFormatter {
	public function formatAsHtml($text);
	public function formatAsText($text, $nl2br, $encode=true);
}

class InvalidFormatter {
	public function formatAsHtml($text) {
		return "<strong>Invalid formatter!</strong><br/><br/>" . nl2br(htmlspecialchars($text, ENT_COMPAT | ENT_HTML401, ""));
	}
	public function formatAsText($text, $nl2br, $encode=true) {
		return "<strong>Invalid formatter!</strong><br/><br/>" . nl2br(htmlspecialchars($text, ENT_COMPAT | ENT_HTML401, ""));
	}
}

$minichanFormatters = array();
function registerFormatter($id, $impl) {
	global $minichanFormatters;
	$minichanFormatters[$id] = $impl;
}

function getFormatter($id) {
	global $minichanFormatters, $disable_errors;
	if($minichanFormatters[$id]) return $minichanFormatters[$id];
	
	// Produce absolute path because file_exists has a different search space than include_once
	$formatterFile = dirname(__FILE__)."/formatters/$id/formatter.php";
	if(file_exists($formatterFile))
		require_once($formatterFile);
	
	if($minichanFormatters[$id]) return $minichanFormatters[$id];
	
	return new InvalidFormatter();
}

function detectFormatter(&$text) {
	if (preg_match('/^(\d+):/', $text, $regs)) {
		$formatter = getFormatter($regs[1]);
		$text = substr($text, strlen($regs[0]));
		return $formatter;
	} else {
		return getFormatter(0);
	}
}

function wrapUserFormatter($text) {
	//TODO check user preferences instead of forcing 1
	return "1:".$text;
}