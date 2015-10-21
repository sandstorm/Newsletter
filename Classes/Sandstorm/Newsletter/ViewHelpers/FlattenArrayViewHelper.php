<?php
/**
 * Created by IntelliJ IDEA.
 * User: sebastian
 * Date: 01.06.15
 * Time: 08:58
 */

namespace Sandstorm\Newsletter\ViewHelpers;


use TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper;

class FlattenArrayViewHelper extends AbstractViewHelper {

	/**
	 * NOTE: This property has been introduced via code migration to ensure backwards-compatibility.
	 * @see AbstractViewHelper::isOutputEscapingEnabled()
	 * @var boolean
	 */
	protected $escapeOutput = FALSE;

	public function render() {
		$inputArray = $this->renderChildren();

		if (!is_array($inputArray)) {
			return array();
		}
		$flattenedArray = array();

		$this->traverseAndFlattenArray($inputArray, $flattenedArray, array());

		return $flattenedArray;
	}

	private function traverseAndFlattenArray($inputArray, &$flattenedArray, $keysSoFar) {
		foreach ($inputArray as $key => $value) {
			$tmp = $keysSoFar;
			$tmp[] = $key;
			if (is_array($value)) {
				$this->traverseAndFlattenArray($value, $flattenedArray, $tmp);
			} else {
				$flattenedArray[implode('.', $tmp)] = $value;
			}
		}

	}


}