<?php
namespace Sandstorm\Newsletter\Domain\Service;

use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Resource\ResourceManager;

/**
 * @Flow\Scope("singleton")
 */
class StyleInliningService {

	/**
	 * @Flow\Inject
	 * @var ResourceManager
	 */
	protected $resourceManager;

	const STYLE_LINK_TAG_REGEX = '/
		<link
		\s*
		rel="stylesheet"
		\s*
		href="([^"]+)"
		\s*
		\/>
	/x';

	public function inlineStyles($html) {
		//return $html;
		// TODO: the following won't work with Cloud Publishing...
		$staticResourceBaseUri = $this->resourceManager->getCollection(ResourceManager::DEFAULT_STATIC_COLLECTION_NAME)->getTarget()->getPublicStaticResourceUri('');

		$stylesheetLinks = array();
		$html = preg_replace_callback(self::STYLE_LINK_TAG_REGEX, function($match) use (&$stylesheetLinks, $staticResourceBaseUri) {
			$stylesheetLink = $match[1];
			$stylesheetLinks[] = FLOW_PATH_WEB . substr($stylesheetLink, strpos($staticResourceBaseUri, '_Resources'));
			return '';
		}, $html);


		$finalCss = '';

		foreach ($stylesheetLinks as $stylesheetLink) {
			$finalCss .= "\n$stylesheetLink\n" . file_get_contents($stylesheetLink) . "\n\n";
		}

		$cssToInlineStyleConverter = new CssToInlineStyles($html, $finalCss);

		return $cssToInlineStyleConverter->convert();
	}
}