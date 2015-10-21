<?php
namespace Sandstorm\Newsletter\Domain\Service\Internal;

use TYPO3\Eel\InterpretedEelParser;
use TYPO3\Flow\Annotations as Flow;

use TYPO3\Eel\ParserException;

/**
 * A compiling expression parser
 *
 * The matcher functions will generate PHP code according to the expressions.
 * Method calls and object / array access are wrapped using the Context object.
 */
class FilterExpressionParser extends InterpretedEelParser {

	public function Disjunction_rgt(&$result, $sub) {
		$lft = $this->unwrap($result['val']);
		$rgt = $this->unwrap($sub['val']);
		$result['val'] = "$lft or $rgt";
	}

	public function Conjunction_rgt(&$result, $sub) {
		$lft = $this->unwrap($result['val']);
		$rgt = $this->unwrap($sub['val']);
		$result['val'] = "$lft and $rgt";
	}

	public function StringLiteral_SingleQuotedStringLiteral(&$result, $sub) {
		// Convert Single Quotes To Double Quotes
		$result['val'] = '"' . (string)str_replace('\\\'', "'", substr($sub['text'], 1, -1)) . '"';

	}
	public function StringLiteral_DoubleQuotedStringLiteral(&$result, $sub) {
		$result['val'] = $sub['text'];
	}

	public function ObjectPath_Identifier(&$result, $sub) {
		$result['val'] = '.' . $sub['text'];
	}

	public function Comparison_rgt(&$result, $sub) {
		$lval = $this->unwrap($result['val']);
		$rval = $this->unwrap($sub['val']);

		switch ($result['comp']) {
			case '==':
				$result['val'] = "$lval == $rval";
				break;
			default:
				throw new ParserException('Unknown comparison operator "' . $result['comp'] . '"', 1344512487);
		}
	}
}
