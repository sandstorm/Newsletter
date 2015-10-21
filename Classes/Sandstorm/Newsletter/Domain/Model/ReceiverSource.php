<?php
namespace Sandstorm\Newsletter\Domain\Model;

use Doctrine\Common\Collections\ArrayCollection;
use TYPO3\Flow\Annotations as Flow;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class ReceiverSource
 * @package Sandstorm\Newsletter\Domain\Model
 * @Flow\Entity
 * @ORM\InheritanceType("SINGLE_TABLE")
 */
abstract class ReceiverSource {

	/**
	 * @var string
	 * @TYPO3\Flow\Annotations\Validate(type="NotEmpty")
	 */
	protected $name;


	/**
	 * @var array
	 */
	protected $dimensionFilter;

	/**
	 * @var \DateTime
	 */
	protected $lastUpdated;

	/**
	 * @var \Doctrine\Common\Collections\ArrayCollection<\Sandstorm\Newsletter\Domain\Model\ReceiverGroup>
	 * @ORM\OneToMany(mappedBy="receiverSource", cascade={"persist", "remove"})
	 */
	protected $receiverGroups;

	public function __construct() {
		$this->receiverGroups = new ArrayCollection();
	}

	public function addReceiverGroup(ReceiverGroup $receiverGroup) {
		$receiverGroup->setReceiverSource($this);
		$this->receiverGroups->add($receiverGroup);
	}

	/**
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * @param string $name
	 */
	public function setName($name) {
		$this->name = $name;
	}

	/**
	 * @return array
	 */
	public function getDimensionFilter() {
		return $this->dimensionFilter;
	}

	/**
	 * @return \DateTime
	 */
	public function getLastUpdated() {
		return $this->lastUpdated;
	}

	/**
	 * @param array $dimensionFilter
	 */
	public function setDimensionFilter($dimensionFilter) {
		$this->dimensionFilter = $dimensionFilter;
	}

	abstract function getConfigurationAsString();

	abstract function getType();

	/**
	 * @return ArrayCollection
	 */
	public function getReceiverGroups() {
		return $this->receiverGroups;
	}

	public function initializeOrUpdate() {
		foreach ($this->receiverGroups as $receiverGroup) {
			/* @var $receiverGroup \Sandstorm\Newsletter\Domain\Model\ReceiverGroup */
			$receiverGroup->updateCacheFile();
		}
		$this->lastUpdated = new \DateTime();
	}

	abstract public function getSourceFileName();

	/**
	 * @param integer $offset
	 * @return mixed
	 */
	public function getSampleData($offset = 0) {
		$file = fopen($this->getSourceFileName(), 'r');

		if ($offset !== 0) {
			fseek($file, $offset - 1);
			$character = fgetc($file);
			if ($character != "\n") {

				// Safeguard if we calculated wrongly!
				fclose($file);
				return $this->getSampleData(0);
			}
		}
		$contents = fgets($file);
		$nextOffset = ftell($file);

		if (!fgets($file)) {
			$nextOffset = 0;
		}

		$previousOffset = 0;
		if ($offset !== 0) {
			// seek to before-line-end
			$previousOffset = $offset - 2;
			fseek($file, $previousOffset);
			while ("\n" !== fgetc($file) && $previousOffset > 0) {
				$previousOffset--;
				fseek($file, $previousOffset);
			}

			$previousOffset++;
		}

		if ($offset === 0) {
			$previousOffset = -1;
		}



		fclose($file);
		$result = json_decode($contents, TRUE);

		$result['__nextOffset'] = $nextOffset;
		$result['__previousOffset'] = $previousOffset;

		return $result;
	}
}