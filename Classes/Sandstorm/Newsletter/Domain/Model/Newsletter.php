<?php
namespace Sandstorm\Newsletter\Domain\Model;

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Persistence\PersistenceManagerInterface;

/**
 * A transient model of a newsletter before it is sent
 */
class Newsletter {

	/**
	 * @var string
	 */
	protected $identifier;

	/**
	 * @var ReceiverGroup
	 */
	protected $receiverGroup;

	/**
	 * @var string
	 */
	protected $htmlContent;

	/**
	 * @var string
	 */
	protected $subject;

	/**
	 * @var string
	 */
	protected $receiverEmailTemplate;

	/**
	 * @var string
	 */
	protected $receiverNameTemplate;

	/**
	 * @var string
	 */
	protected $senderEmailTemplate;

	/**
	 * @var string
	 */
	protected $senderNameTemplate;

	/**
	 * @var string
	 */
	protected $replyToEmailTemplate;

	/**
	 * @var string
	 */
	protected $newsletterLink;

	/**
	 * @var string
	 */
	protected $unsubscribeLink;

	/**
	 * @return string
	 */
	public function getIdentifier() {
		return $this->identifier;
	}

	/**
	 * @param string $identifier
	 */
	public function setIdentifier($identifier) {
		$this->identifier = $identifier;
	}

	/**
	 * @return ReceiverGroup
	 */
	public function getReceiverGroup() {
		return $this->receiverGroup;
	}

	/**
	 * @param ReceiverGroup $receiverGroup
	 */
	public function setReceiverGroup($receiverGroup) {
		$this->receiverGroup = $receiverGroup;
	}

	/**
	 * @return string
	 */
	public function getHtmlContent() {
		return $this->htmlContent;
	}

	/**
	 * @param string $htmlContent
	 */
	public function setHtmlContent($htmlContent) {
		$this->htmlContent = $htmlContent;
	}

	/**
	 * @return string
	 */
	public function getSubject() {
		return $this->subject;
	}

	/**
	 * @param string $subject
	 */
	public function setSubject($subject) {
		$this->subject = $subject;
	}

	/**
	 * @return string
	 */
	public function getReceiverEmailTemplate() {
		return $this->receiverEmailTemplate;
	}

	/**
	 * @param string $receiverEmailTemplate
	 */
	public function setReceiverEmailTemplate($receiverEmailTemplate) {
		$this->receiverEmailTemplate = $receiverEmailTemplate;
	}

	/**
	 * @return string
	 */
	public function getReceiverNameTemplate() {
		return $this->receiverNameTemplate;
	}

	/**
	 * @param string $receiverNameTemplate
	 */
	public function setReceiverNameTemplate($receiverNameTemplate) {
		$this->receiverNameTemplate = $receiverNameTemplate;
	}

	/**
	 * @return string
	 */
	public function getSenderEmailTemplate() {
		return $this->senderEmailTemplate;
	}

	/**
	 * @param string $senderEmailTemplate
	 */
	public function setSenderEmailTemplate($senderEmailTemplate) {
		$this->senderEmailTemplate = $senderEmailTemplate;
	}

	/**
	 * @return string
	 */
	public function getSenderNameTemplate() {
		return $this->senderNameTemplate;
	}

	/**
	 * @param string $senderNameTemplate
	 */
	public function setSenderNameTemplate($senderNameTemplate) {
		$this->senderNameTemplate = $senderNameTemplate;
	}

	/**
	 * @return string
	 */
	public function getReplyToEmailTemplate() {
		return $this->replyToEmailTemplate;
	}

	/**
	 * @param string $replyToEmailTemplate
	 */
	public function setReplyToEmailTemplate($replyToEmailTemplate) {
		$this->replyToEmailTemplate = $replyToEmailTemplate;
	}

	/**
	 * @return string
	 */
	public function getNewsletterLink() {
		return $this->newsletterLink;
	}

	/**
	 * @param string $newsletterLink
	 */
	public function setNewsletterLink($newsletterLink) {
		$this->newsletterLink = $newsletterLink;
	}

	/**
	 * @return string
	 */
	public function getUnsubscribeLink() {
		return $this->unsubscribeLink;
	}

	/**
	 * @param string $unsubscribeLink
	 */
	public function setUnsubscribeLink($unsubscribeLink) {
		$this->unsubscribeLink = $unsubscribeLink;
	}
}