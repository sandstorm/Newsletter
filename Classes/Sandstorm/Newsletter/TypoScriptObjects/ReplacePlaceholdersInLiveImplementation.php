<?php
/**
 * Created by IntelliJ IDEA.
 * User: sebastian
 * Date: 05.05.15
 * Time: 15:33
 */

namespace Sandstorm\Newsletter\TypoScriptObjects;


use TYPO3\Flow\Reflection\ObjectAccess;
use TYPO3\TypoScript\TypoScriptObjects\AbstractTypoScriptObject;

class ReplacePlaceholdersInLiveImplementation extends AbstractTypoScriptObject {

	const PLACEHOLDER_REGEX = '/{([a-zA-Z.]+)}/';

	/**
	 * Evaluate this TypoScript object and return the result
	 *
	 * @return mixed
	 */
	public function evaluate() {
		$value = $this->tsValue('value');
		$isActive = $this->tsValue('isActive');

		$sampleData = $this->tsValue('sampleData');
		if ($isActive) {
			$value = preg_replace_callback(self::PLACEHOLDER_REGEX, function($element) use ($sampleData) {
				return ObjectAccess::getPropertyPath($sampleData, $element[1]);
			}, $value);

			return $value;
		} else {
			return $value;
		}
	}
}