<?php
namespace Sandstorm\Newsletter\Controller;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Sandstorm.Newsletter".  *
 *                                                                        *
 *                                                                        */

use Sandstorm\Newsletter\Domain\Model\CsvReceiverSource;
use Sandstorm\Newsletter\Domain\Model\JsonReceiverSource;
use Sandstorm\Newsletter\Domain\Model\ReceiverGroup;
use Sandstorm\Newsletter\Domain\Model\ReceiverSource;
use Sandstorm\Newsletter\Domain\Model\UnsubscribeList;
use Sandstorm\Newsletter\Domain\Repository\ReceiverGroupRepository;
use Sandstorm\Newsletter\Domain\Repository\ReceiverSourceRepository;
use Sandstorm\Newsletter\Domain\Repository\UnsubscribeListRepository;
use Sandstorm\Newsletter\Domain\Service\ReceiverGroupGenerationService;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Utility\Files;
use TYPO3\Neos\Controller\Module\AbstractModuleController;
use TYPO3\Neos\Domain\Service\ContentDimensionPresetSourceInterface;

class ReceiverGroupModuleController extends AbstractModuleController {

	/**
	 * @Flow\Inject
	 * @var ReceiverSourceRepository
	 */
	protected $receiverSourceRepository;

	/**
	 * @Flow\Inject
	 * @var ReceiverGroupRepository
	 */
	protected $receiverGroupRepository;

	/**
	 * @Flow\Inject
	 * @var UnsubscribeListRepository
	 */
	protected $unsubscribeListRepository;

	/**
	 * @Flow\Inject
	 * @var ReceiverGroupGenerationService
	 */
	protected $receiverGroupGenerationService;

	/**
	 * @Flow\Inject
	 * @var ContentDimensionPresetSourceInterface
	 */
	protected $contentDimensionPresetSource;

	/**
	 * @Flow\InjectConfiguration(path="receiverSourceTypes")
	 * @var array
	 */
	protected $receiverSourceTypes;

	public function initializeAction() {
		parent::initializeAction();
		if ($this->arguments->hasArgument('receiverSource')) {
			if (!$this->request->hasArgument('receiverSourceType')) {
				throw new \Exception('TODO: receiverSourceType not specified.');
			}
			$receiverSourceType = $this->request->getArgument('receiverSourceType');
			if (!isset($this->receiverSourceTypes[$receiverSourceType])) {
				throw new \Exception(sprintf('TODO: receiverSourceType "%s" not configured.', $receiverSourceType));
			}
			if (!isset($this->receiverSourceTypes[$receiverSourceType]['className'])) {
				throw new \Exception(sprintf('TODO: receiverSourceType "%s" does not have a class name configured.', $receiverSourceType));
			}

			$this->arguments->getArgument('receiverSource')->setDataType($this->receiverSourceTypes[$receiverSourceType]['className']);
			$this->initializeActionMethodValidators();

		}
	}

	/**
	 * @return void
	 */
	public function indexAction() {
		$this->view->assign('receiverSources', $this->receiverSourceRepository->findAll());
		$this->view->assign('receiverSourceTypes', $this->receiverSourceTypes);
		$this->view->assign('unsubscribeLists', $this->unsubscribeListRepository->findAll());
	}

	/**
	 * -------------------------------- RECEIVER SOURCES -----------------------------
	 */

	/**
	 * @param string $receiverSourceType
	 * @return void
	 */
	public function newAction($receiverSourceType) {
		$this->assignDimensionPresets();
		$this->view->assign('receiverSourceType', $receiverSourceType);
	}

	/**
	 * @param ReceiverSource $receiverSource
	 * @return void
	 */
	public function createAction(ReceiverSource $receiverSource) {
		$receiverGroup = new ReceiverGroup();
		$receiverGroup->setName('all');
		$receiverGroup->setFilter('');

		$unsubscribeList = $this->unsubscribeListRepository->findAll()->getFirst();
		if ($unsubscribeList === NULL) {
			$unsubscribeList = new UnsubscribeList();
			$unsubscribeList->setName('Default');
			$this->unsubscribeListRepository->add($unsubscribeList);
		}

		$receiverGroup->setUnsubscribeList($unsubscribeList);
		$receiverSource->addReceiverGroup($receiverGroup);

		$receiverSource->initializeOrUpdate();
		$this->receiverSourceRepository->add($receiverSource);
		$this->redirect('index');
	}

	/**
	 * @param ReceiverSource $receiverSource
	 * @param string $receiverSourceType
	 * @return void
	 * @Flow\IgnoreValidation(argumentName="receiverSource")
	 */
	public function editAction(ReceiverSource $receiverSource, $receiverSourceType) {
		$this->assignDimensionPresets();
		$this->view->assign('receiverSource', $receiverSource);
		$this->view->assign('receiverSourceType', $receiverSourceType);
	}

	/**
	 * @param ReceiverSource $receiverSource
	 * @param integer $offset
	 * @param string $receiverSourceType
	 * @return void
	 * @Flow\IgnoreValidation(argumentName="receiverSource")
	 */
	public function previewAction(ReceiverSource $receiverSource, $offset = 0) {
		$this->assignDimensionPresets();
		$this->view->assign('receiverSource', $receiverSource);

		$sampleData = $receiverSource->getSampleData($offset);
		$nextOffset = $sampleData['__nextOffset'];
		unset($sampleData['__nextOffset']);
		$previousOffset = $sampleData['__previousOffset'];
		unset($sampleData['__previousOffset']);
		$this->view->assign('sampleData', $sampleData);
		$this->view->assign('nextOffset', $nextOffset);
		$this->view->assign('previousOffset', $previousOffset);

	}

	/**
	 * @param ReceiverSource $receiverSource
	 * @return void
	 */
	public function updateAction(ReceiverSource $receiverSource) {
		$receiverSource->initializeOrUpdate();
		$this->receiverSourceRepository->update($receiverSource);
		$this->redirect('index');
	}

	/**
	 * @param ReceiverSource $receiverSource
	 * @return void
	 */
	public function refreshAction(ReceiverSource $receiverSource) {
		$receiverSource->initializeOrUpdate();
		$this->receiverSourceRepository->update($receiverSource);
		$this->persistenceManager->whitelistObject($receiverSource);
		$this->redirect('index');
	}

	/**
	 * @param ReceiverSource $receiverSource
	 * @return void
	 */
	public function deleteAction(ReceiverSource $receiverSource) {
		$this->receiverSourceRepository->remove($receiverSource);
		$this->redirect('index');
	}

	private function assignDimensionPresets() {
		$presets = $this->contentDimensionPresetSource->getAllPresets();
		$this->view->assign('dimensionPresets', $presets['language']['presets']);
	}

	/**
	 * -------------------------------- RECEIVER GROUP -----------------------------
	 */

	/**
	 * @param ReceiverSource $receiverSource
	 */
	public function newReceiverGroupAction(ReceiverSource $receiverSource) {
		$this->view->assign('receiverSource', $receiverSource);
	}

	/**
	 * @param ReceiverGroup $receiverGroup
	 */
	public function createReceiverGroupAction(ReceiverGroup $receiverGroup) {
		$receiverGroup->updateCacheFile();
		$this->receiverGroupRepository->add($receiverGroup);
		$this->redirect('index');
	}

	/**
	 * @param ReceiverGroup $receiverGroup
	 */
	public function editReceiverGroupAction(ReceiverGroup $receiverGroup) {
		$this->view->assign('receiverGroup', $receiverGroup);
		$this->view->assign('unsubscribeLists', $this->unsubscribeListRepository->findAll());
	}

	/**
	 * @param ReceiverGroup $receiverGroup
	 * @throws \TYPO3\Flow\Persistence\Exception\IllegalObjectTypeException
	 */
	public function updateReceiverGroupAction(ReceiverGroup $receiverGroup) {
		$receiverGroup->updateCacheFile();
		$this->receiverGroupRepository->update($receiverGroup);
		$this->redirect('index');
	}

	/**
	 * @param ReceiverGroup $receiverGroup
	 * @throws \TYPO3\Flow\Persistence\Exception\IllegalObjectTypeException
	 */
	public function deleteReceiverGroupAction(ReceiverGroup $receiverGroup) {
		$this->receiverGroupRepository->remove($receiverGroup);
		$this->redirect('index');
	}

	/**
	 * @param ReceiverGroup $receiverGroup
	 */
	public function previewReceiverGroupAction(ReceiverGroup $receiverGroup) {
		$presets = $this->contentDimensionPresetSource->getAllPresets();
		$languages = array_keys($presets['language']['presets']);


		$exampleDataPerLanguage = array();
		foreach ($languages as $language) {
			$file = fopen($receiverGroup->getCacheFileName($language), 'r');
			$contents = fgets($file);
			fclose($file);


			$exampleDataPerLanguage[$language] = array(
				'numberOfReceivers' => $receiverGroup->getNumberOfReceivers($language),
				'singleLine' => json_decode($contents, TRUE)
			);
		}

		$this->view->assign('exampleDataPerLanguage', $exampleDataPerLanguage);
		$this->view->assign('receiverGroup', $receiverGroup);
	}

	/**
	 * -------------------------------- UNSUBSCRIBE LIST -----------------------------
	 */

	public function newUnsubscribeListAction() {
	}

	/**
	 * @param UnsubscribeList $unsubscribeList
	 */
	public function createUnsubscribeListAction(UnsubscribeList $unsubscribeList) {
		$this->unsubscribeListRepository->add($unsubscribeList);
		$this->redirect('index');
	}

	/**
	 * @param UnsubscribeList $unsubscribeList
	 */
	public function deleteUnsubscribeListAction(UnsubscribeList $unsubscribeList) {
		$this->unsubscribeListRepository->remove($unsubscribeList);
		$this->redirect('index');
	}

	/**
	 * @param UnsubscribeList $unsubscribeList
	 */
	public function editUnsubscribeListAction(UnsubscribeList $unsubscribeList) {
		$this->view->assign('unsubscribeList', $unsubscribeList);
	}

	/**
	 * @param UnsubscribeList $unsubscribeList
	 */
	public function updateUnsubscribeListAction(UnsubscribeList $unsubscribeList) {
		$this->unsubscribeListRepository->update($unsubscribeList);
		$this->redirect('index');
	}

    /**
     * @param CsvReceiverSource $receiverSource
     * @param string $receiverSourceType
     */
    public function downloadCsvSourceAction(ReceiverSource $receiverSource, $receiverSourceType)
    {
        $filename = $receiverSource->getName() . '.csv';

        $content = \explode(chr(10), Files::getFileContents($receiverSource->getSourceFileName()));
        $header = \array_keys(\json_decode(\reset($content), true));

        ob_start();
        $fh = fopen('php://output', 'w');
        fputcsv($fh, $header);
        foreach ($content as $record) {
            $record = \json_decode($record, true);
            if ($record === null) {
                continue;
            }
            $record = \array_values($record);
            foreach ($record as $propertyName => $propertyValue) {
                if (!\is_scalar($propertyValue)) {
                    unset($record[$propertyName]);
                }
            }
            fputcsv($fh, $record);
        }
        $content = ob_get_clean();

        $this->outputCsv(function () use ($content) {
            echo $content;
        }, $receiverSource->getName() . '.csv', \mb_strlen($content));
    }

	/**
	 * @param UnsubscribeList $unsubscribeList
	 */
	public function downloadUnsubscribeListAction(UnsubscribeList $unsubscribeList) {

	    if (\is_file($unsubscribeList->getUnsubscribeFileName())) {
            $fp = fopen($unsubscribeList->getUnsubscribeFileName(), 'r');
            $this->outputCsv(function () use ($fp) {
                fpassthru($fp);
            }, 'UnsubscribeList.csv', filesize($unsubscribeList->getUnsubscribeFileName()));
        }
        $this->outputCsv();
	}

	protected function outputCsv(\Closure $output = null, $filename = 'export.csv', $filesize = 0) {
        header('Content-Type: text/csv');
        header('Content-disposition: attachment;filename=' . $filename);
        header('Content-Length: ' . $filesize);
        $output ? $output() : null;
        exit;
    }
}
