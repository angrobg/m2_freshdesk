<?php

namespace Morfdev\Freshdesk\Model\Source;

use Magento\Framework\View\LayoutInterface;
use Magento\Bundle\Model\Product\Type as TypeBundle;
use Magento\Bundle\Block\Sales\Order\Items\Renderer as RendererBundle;
use Magento\Downloadable\Model\Product\Type as TypeDownloadable;
use Magento\Downloadable\Block\Sales\Order\Item\Renderer\Downloadable as RendererDownloadable;
use Magento\Sales\Block\Order\Item\Renderer\DefaultRenderer;

class RendererType
{
    /** @var LayoutInterface  */
    protected $layout;

    /**
     * @param LayoutInterface $layout
     */
    public function __construct(
        LayoutInterface $layout
    ) {
        $this->layout = $layout;
    }

    /**
     * @param $productType
     * @return RendererBundle|RendererDownloadable|DefaultRenderer
     */
    public function getProductRendererByType($productType)
    {
        switch ($productType) {
            case TypeBundle::TYPE_CODE:
                /** @var RendererBundle $renderer */
                $renderer = $this->layout->createBlock(RendererBundle::class);
                $renderer->setTemplate('Morfdev_Freshdesk::renderer/bundle.phtml');
                break;
            case TypeDownloadable::TYPE_DOWNLOADABLE:
                /** @var RendererDownloadable $renderer */
                $renderer = $this->layout->createBlock(RendererDownloadable::class);
                $renderer->setTemplate('Morfdev_Freshdesk::renderer/downloadable.phtml');
                break;
            default:
                /** @var DefaultRenderer $renderer */
                $renderer = $this->layout->createBlock(DefaultRenderer::class);
                $renderer->setTemplate('Morfdev_Freshdesk::renderer/default.phtml');
        }
        return $renderer;
    }
}