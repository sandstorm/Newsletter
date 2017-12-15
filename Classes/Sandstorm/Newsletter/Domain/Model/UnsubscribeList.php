<?php
/**
 * Created by IntelliJ IDEA.
 * User: sebastian
 * Date: 27.05.15
 * Time: 12:59
 */

namespace Sandstorm\Newsletter\Domain\Model;

use TYPO3\Flow\Annotations as Flow;
use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Persistence\PersistenceManagerInterface;
use TYPO3\Flow\Resource\Resource;
use TYPO3\Flow\Utility\Files;

/**
 * @Flow\Entity
 * @ORM\HasLifecycleCallbacks
 */
class UnsubscribeList {

	/**
	 * @var string
	 * @Flow\InjectConfiguration("receiverGroupCache")
	 * @Flow\Transient
	 */
	protected $receiverGroupCache;

	/**
	 * @Flow\Inject
	 * @var PersistenceManagerInterface
	 */
	protected $persistenceManager;

	/**
	 * @var string
	 */
	protected $name;

	/**
	 * This resource is not imported into the database, but we use it for a file upload in Neos.
	 *
	 * @var Resource
	 * @Flow\Transient
	 * @Flow\Validate(type="TYPO3\Form\Validation\FileTypeValidator", options={ "allowedExtensions"={"csv"} })
	 */
	protected $unsubscribeFile;


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

	public function getUnsubscribeFileName() {
		return $this->receiverGroupCache . '/_UNSUBSCRIBE_' . $this->persistenceManager->getIdentifierByObject($this);
	}

	public function unsubscribeEmail($email) {
		Files::createDirectoryRecursively($this->receiverGroupCache);
		file_put_contents($this->getUnsubscribeFileName(), strtolower($email) . ';' . (new \DateTime())->format('Y-m-d H:i:s') . "\n", FILE_APPEND);
		$this->emitEmailUnsubscribed($email, $this);
	}

	/**
	 * @param Resource $uploadedResource
	 */
	public function setUnsubscribeFile(Resource $uploadedResource) {
		$this->unsubscribeFile = $uploadedResource;
	}

	/**
	 * @return mixed
	 */
	public function getUnsubscribeFile() {
		return $this->unsubscribeFile;
	}

	/**
	 * @ORM\PreFlush
	 */
	public function writeUnsubscribeFile() {
		if ($this->unsubscribeFile) {
			rename($this->unsubscribeFile->createTemporaryLocalCopy(), $this->getUnsubscribeFileName());
		}
	}

    /**
     * @param string $email
     * @param UnsubscribeList $list
     * @Flow\Signal
     */
	public function emitEmailUnsubscribed($email, UnsubscribeList $list)
    {

    }
}
