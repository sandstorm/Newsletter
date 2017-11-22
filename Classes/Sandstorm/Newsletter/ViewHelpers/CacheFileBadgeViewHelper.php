<?php
/**
 * Created by IntelliJ IDEA.
 * User: sebastian
 * Date: 01.06.15
 * Time: 08:58
 */

namespace Sandstorm\Newsletter\ViewHelpers;


use Sandstorm\Newsletter\Domain\Model\ReceiverGroup;
use TYPO3\Flow\Utility\Arrays;
use TYPO3\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\Neos\Domain\Service\ContentDimensionPresetSourceInterface;
use TYPO3\Flow\Annotations as Flow;

class CacheFileBadgeViewHelper extends AbstractViewHelper {

	protected $escapeOutput = false;

    /**
     * @Flow\Inject
     * @var ContentDimensionPresetSourceInterface
     */
    protected $contentDimensionPresetSource;

	public function render(ReceiverGroup $group)
    {
	    $presetIdentifiers = Arrays::trimExplode(', ', $group->getCacheFiles());
	    $presets = $this->contentDimensionPresetSource->getAllPresets();
	    $arguments = \array_map(function ($identifier) use ($presets, $group) {
	        return [
	            'label' => $presets['language']['presets'][$identifier]['label'],
                'subscribers' => $group->getNumberOfReceivers($identifier)
            ];
        }, $presetIdentifiers);

        return self::renderStatic($arguments, $this->buildRenderChildrenClosure(), $this->renderingContext);
	}

    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        $templateVariableContainer = $renderingContext->getTemplateVariableContainer();

        $output = '';
        foreach ($arguments as $item) {
            $templateVariableContainer->add('label', $item['label']);
            $templateVariableContainer->add('subscribers', $item['subscribers']);
            $output .= $renderChildrenClosure();
            $templateVariableContainer->remove('label');
            $templateVariableContainer->remove('subscribers');
        }
        return $output;
    }
}
