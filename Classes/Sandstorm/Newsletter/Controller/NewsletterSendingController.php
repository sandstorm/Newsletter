<?php
namespace Sandstorm\Newsletter\Controller;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Sandstorm.Newsletter".  *
 *                                                                        *
 *                                                                        */

use GuzzleHttp\Client;
use Sandstorm\Newsletter\Domain\Model\Newsletter;
use Sandstorm\Newsletter\Domain\Repository\ReceiverGroupRepository;
use Sandstorm\Newsletter\Domain\Repository\ReceiverSourceRepository;
use Sandstorm\Newsletter\Domain\Service\NewsletterSendingService;
use Sandstorm\Newsletter\Domain\Service\StyleInliningService;
use TYPO3\Eel\FlowQuery\FlowQuery;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Mvc\Controller\ActionController;
use TYPO3\Neos\Domain\Service\ContentDimensionPresetSourceInterface;
use TYPO3\Neos\Service\LinkingService;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;

class NewsletterSendingController extends ActionController {

	/**
	 * @Flow\Inject
	 * @var NewsletterSendingService
	 */
	protected $newsletterSendingService;

	/**
	 * @Flow\Inject
	 * @var ReceiverGroupRepository
	 */
	protected $receiverGroupRepository;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Neos\View\TypoScriptView
	 */
	protected $newsletterRenderingView;

	/**
	 * @Flow\Inject
	 * @var StyleInliningService
	 */
	protected $styleInliningService;

	/**
	 * @Flow\Inject
	 * @var ContentDimensionPresetSourceInterface
	 */
	protected $contentDimensionPresetSource;

	/**
	 * @Flow\Inject
	 * @var LinkingService
	 */
	protected $linkingService;

	/**
	 * @param NodeInterface $node
	 */
	public function statusAction(NodeInterface $node) {

		$presets = $this->contentDimensionPresetSource->getAllPresets();

		$newsletterIdentifiers = array();
		if (!isset($presets['language']['presets'])) {
			$newsletterIdentifiers[] = $node->getIdentifier();
		} else {
			foreach ($presets['language']['presets'] as $languageKey => $languageConfiguration) {
				$newsletterIdentifiers[] = $node->getIdentifier() . '_' . $languageKey;
			}
		}

		$status = $this->newsletterSendingService->getStatus($newsletterIdentifiers);
		$status['serverConfiguration'] = $this->newsletterSendingService->getConfiguration();


		return json_encode($status);
	}

	/**
	 * @param NodeInterface $node
	 */
	public function cancelAction(NodeInterface $node) {

		$presets = $this->contentDimensionPresetSource->getAllPresets();

		$newsletterIdentifiers = array();
		if (!isset($presets['language']['presets'])) {
			$newsletterIdentifiers[] = $node->getIdentifier();
		} else {
			foreach ($presets['language']['presets'] as $languageKey => $languageConfiguration) {
				$newsletterIdentifiers[] = $node->getIdentifier() . '_' . $languageKey;
			}
		}

		return json_encode($this->newsletterSendingService->cancel($newsletterIdentifiers));
	}

	/**
	 * @param NodeInterface $node
	 */
	public function failuresAction(NodeInterface $node) {
		$presets = $this->contentDimensionPresetSource->getAllPresets();

		$newsletterIdentifiers = array();
		if (!isset($presets['language']['presets'])) {
			$newsletterIdentifiers[] = $node->getIdentifier();
		} else {
			foreach ($presets['language']['presets'] as $languageKey => $languageConfiguration) {
				$newsletterIdentifiers[] = $node->getIdentifier() . '_' . $languageKey;
			}
		}

		$this->response->setHeader('Content-Disposition', 'attachment; filename=errors.csv');

		$errorFileName = $this->newsletterSendingService->getFailures($newsletterIdentifiers);
		$resource = fopen($errorFileName, 'r');
		fpassthru($resource);
		fclose($resource);

		return FALSE;
	}

	/**
	 * @param NodeInterface $node
	 * @param string $previewEmail
	 */
	public function indexAction(NodeInterface $node, $previewEmail = NULL) {
		$this->newsletterRenderingView->setControllerContext($this->getControllerContext());
		$this->newsletterRenderingView->setOption('enableContentCache', FALSE);
		$this->newsletterRenderingView->assign('value', $node);
		$this->newsletterRenderingView->assign('editPreviewMode', 'finalNewsletterRendering');


		$presets = $this->contentDimensionPresetSource->getAllPresets();

		if (!isset($presets['language']['presets'])) {
			$this->sendNewsletter($node, $previewEmail);
		} else {
			foreach ($presets['language']['presets'] as $languageKey => $languageConfiguration) {
				$this->sendNewsletter($node, $previewEmail, $languageKey, $languageConfiguration);
			}
		}

		return json_encode(array('success' => TRUE));
	}

	private function sendNewsletter(NodeInterface $node, $previewEmail = NULL, $languageKey = NULL, array $languageConfiguration = NULL) {

		// The Receiver Group association is specified in each individual node dimension,
		// but as the user submitted it in a certain Node Dimension, we're using *exactly* this
		// $receiverGroup which the user has submitted.
		/* @var $receiverGroup \Sandstorm\Newsletter\Domain\Model\ReceiverGroup */
		$receiverGroup = $this->receiverGroupRepository->findByIdentifier($node->getProperty('receiverGroup'));
		if ($receiverGroup == NULL) {
			// TODO: log!
			return;
		}

		$context = [
			'workspaceName' => 'live'
		];

		if ($languageKey) {
			$context['dimensions'] = array(
				'language' => $languageConfiguration['values']
			);
			$context['targetDimensions'] = array(
				'language' => reset($languageConfiguration['values'])
			);
		}

		/* @var $nodeInCorrectDimension NodeInterface */
		$nodeInCorrectDimension = (new FlowQuery(array($node)))->context($context)->get(0);

		if ($nodeInCorrectDimension == NULL) {
			// Skip un-existing nodes
			return;
		}

		$this->newsletterRenderingView->assign('value', $nodeInCorrectDimension);
		$html = $this->newsletterRenderingView->render();
		$html = $this->styleInliningService->inlineStyles($html);

		$newsletter = new Newsletter();

		$newsletterIdentifier = $node->getIdentifier();
		if ($languageKey) {
			$newsletterIdentifier .= '_' . $languageKey;
		}

		if ($previewEmail) {
			$newsletterIdentifier .= uniqid('__', true);
		}

		$newsletter->setIdentifier($newsletterIdentifier);
		$newsletter->setHtmlContent($html);

		$newsletter->setSubject($nodeInCorrectDimension->getProperty('subjectTemplate'));
		if ($previewEmail !== NULL) {
			$newsletter->setReceiverEmailTemplate($previewEmail);
			$newsletter->setReceiverGroup($receiverGroup->singlePersonReceiverGroup());
		} else {
			$newsletter->setReceiverEmailTemplate($nodeInCorrectDimension->getProperty('receiverEmailTemplate'));
			$newsletter->setReceiverGroup($receiverGroup);
		}

		$newsletter->setReceiverNameTemplate($nodeInCorrectDimension->getProperty('receiverNameTemplate'));
		$newsletter->setSenderEmailTemplate($nodeInCorrectDimension->getProperty('senderEmailTemplate'));
		$newsletter->setSenderNameTemplate($nodeInCorrectDimension->getProperty('senderNameTemplate'));
		$newsletter->setReplyToEmailTemplate($nodeInCorrectDimension->getProperty('replyToEmailTemplate'));
		$newsletter->setNewsletterLink($this->linkingService->createNodeUri($this->getControllerContext(), $nodeInCorrectDimension, NULL, NULL, TRUE));

		$unsubscribeListIdentifier = null;
		if ($receiverGroup->getUnsubscribeList()) {
			$unsubscribeListIdentifier = $this->persistenceManager->getIdentifierByObject($receiverGroup->getUnsubscribeList());
		}
		$newsletter->setUnsubscribeLink($this->uriBuilder->reset()->setCreateAbsoluteUri(TRUE)->uriFor('unsubscribe', array('unsubscribeList' => $unsubscribeListIdentifier), 'Unsubscribe', 'Sandstorm.Newsletter'));

		$this->newsletterSendingService->sendNewsletter($newsletter, $languageKey);
	}
}