<?php
/**
 * Created by IntelliJ IDEA.
 * User: sebastian
 * Date: 27.05.15
 * Time: 13:15
 */

namespace Sandstorm\Newsletter\Domain\Model;

use Doctrine\Common\Collections\ArrayCollection;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Persistence\PersistenceManagerInterface;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class CsvReceiverSource
 * @package Sandstorm\Newsletter\Domain\Model
 * @Flow\Entity
 */
class JsonReceiverSource extends ReceiverSource {

	/**
	 * @var string
	 * @Flow\Validate(type="Sandstorm\Newsletter\Validator\ExistingFileValidator")
	 * @Flow\Validate(type="NotEmpty")
	 */
	protected $sourceFileName;


	/**
	 * @return string
	 */
	public function getSourceFileName() {
		return $this->sourceFileName;
	}

		/**
		 * @param string $sourceFileName
		 */
	public function setSourceFileName($sourceFileName) {
		$this->sourceFileName = $sourceFileName;
	}

	public function getConfigurationAsString() {
		return $this->sourceFileName;
	}

	public function getType() {
		return 'json';
	}
}