<?php
/**
 * Created by IntelliJ IDEA.
 * User: sebastian
 * Date: 05.05.15
 * Time: 15:16
 */

namespace Sandstorm\Newsletter\Validator;


use Sandstorm\Newsletter\Domain\Model\ReceiverSource;
use Sandstorm\Newsletter\Domain\Repository\ReceiverSourceRepository;
use TYPO3\Eel\ProtectedContextAwareInterface;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Validation\Validator\AbstractValidator;

/**
 */
class ExistingFileValidator extends AbstractValidator {


	/**
	 * Check if $value is valid. If it is not valid, needs to add an error
	 * to Result.
	 *
	 * @param mixed $value
	 * @return void
	 * @throws \TYPO3\Flow\Validation\Exception\InvalidValidationOptionsException if invalid validation options have been specified in the constructor
	 */
	protected function isValid($value) {
		if (!file_exists($value)) {
			$this->addError('File "' . $value .'" does not exist.', 1431000171);
		}
	}
}