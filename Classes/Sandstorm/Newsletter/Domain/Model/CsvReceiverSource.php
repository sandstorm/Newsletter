<?php
/**
 * Created by IntelliJ IDEA.
 * User: sebastian
 * Date: 27.05.15
 * Time: 13:15
 */

namespace Sandstorm\Newsletter\Domain\Model;
use Doctrine\Common\Collections\ArrayCollection;
use League\Csv\Reader;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Persistence\PersistenceManagerInterface;
use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Resource\Resource;
use TYPO3\Flow\Utility\Files;

/**
 * Class CsvReceiverSource
 * @package Sandstorm\Newsletter\Domain\Model
 * @Flow\Entity
 */
class CsvReceiverSource extends ReceiverSource {

	/**
	 * @var Resource
	 *
	 * @ORM\OneToOne
	 * @Flow\Validate(type="TYPO3\Form\Validation\FileTypeValidator", options={ "allowedExtensions"={"csv"} })
	 * @Flow\Validate(type="NotEmpty")
	 */
	protected $sourceFile;


	/**
	 * @Flow\Inject
	 * @var PersistenceManagerInterface
	 */
	protected $persistenceManager;

	/**
	 * @var string
	 * @Flow\InjectConfiguration("receiverGroupCache")
	 * @Flow\Transient
	 */
	protected $receiverGroupCache;

	public function getConfigurationAsString() {
		// TODO: Implement getConfigurationAsString() method.
	}

	public function getType() {
		return 'csv';
	}

	/**
	 * @return Resource
	 */
	public function getSourceFile() {
		return $this->sourceFile;
	}

	/**
	 * @param Resource $sourceFile
	 */
	public function setSourceFile(Resource $sourceFile) {
		$this->sourceFile = $sourceFile;
	}


	public function getSourceFileName() {
		return $this->receiverGroupCache . '/_CSV_' . $this->persistenceManager->getIdentifierByObject($this);
	}

	public function initializeOrUpdate() {
		$localCopyForCsvFile = $this->sourceFile->createTemporaryLocalCopy();
		$csvReader = Reader::createFromPath($localCopyForCsvFile);
		$csvReader->setDelimiter(';');
		$csv = $csvReader->fetchAssoc();

		$output = array();
		foreach ($csv as $row) {
			$output[] = json_encode($row);
		}

		Files::createDirectoryRecursively(dirname($this->getSourceFileName()));

		file_put_contents($this->getSourceFileName(), implode("\n", $output));

		parent::initializeOrUpdate();
	}
}
