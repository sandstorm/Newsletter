<?php
namespace Sandstorm\Newsletter\Domain\Service\Internal;

use TYPO3\Eel\Context;
use TYPO3\Eel\EelEvaluatorInterface;
use TYPO3\Eel\ParserException;
use TYPO3\Flow\Annotations as Flow;

/**
 * An evaluator that compiles expressions down to PHP code
 *
 * This simple implementation will lazily parse and evaluate the generated PHP
 * code into a function with a name built from the hashed expression.
 *
 * @Flow\Scope("singleton")
 */
class FilterExpressionEvaluator implements EelEvaluatorInterface {

	/**
	 * Evaluate an expression under a given context
	 *
	 * @param string $expression
	 * @param Context $context
	 * @return mixed
	 * @throws ParserException
	 */
	public function evaluate($expression, Context $context) {
		$parser = new FilterExpressionParser($expression, $context);
		$res = $parser->match_Expression();

		if ($res === FALSE) {
			throw new ParserException(sprintf('Expression "%s" could not be parsed.', $expression), 1344514198);
		} elseif ($parser->pos !== strlen($expression)) {
			throw new ParserException(sprintf('Expression "%s" could not be parsed. Error starting at character %d: "%s".', $expression, $parser->pos, substr($expression, $parser->pos)), 1344514188);
		} elseif (!array_key_exists('val', $res)) {
			throw new ParserException(sprintf('Parser error, no val in result %s ', json_encode($res)), 1344514204);
		}

		if ($res['val'] instanceof Context) {
			$result = $res['val']->unwrap();
		} else {
			$result = $res['val'];
		}
		return "select($result)";
	}
}