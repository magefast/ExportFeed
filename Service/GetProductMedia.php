<?php

namespace Magefast\ExportFeed\Service;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Gallery\ReadHandler as GalleryReadHandler;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;

class GetProductMedia
{
    /**
     * @var string
     */
    private string $mediaUrl = '';

    /**
     * @var GalleryReadHandler
     */
    private GalleryReadHandler $galleryReadHandler;

    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @param GalleryReadHandler $galleryReadHandler
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        GalleryReadHandler    $galleryReadHandler,
        StoreManagerInterface $storeManager
    )
    {
        $this->galleryReadHandler = $galleryReadHandler;
        $this->storeManager = $storeManager;
    }

    /**
     * @throws NoSuchEntityException
     */
    public function prepareProductImages(Product $product): array
    {
        $data = [];
        $data['productImage1'] = null;
        $data['productImage2'] = null;
        $data['productImage3'] = null;
        $data['productImage4'] = null;
        $data['productImage5'] = null;
        $data['productImage6'] = null;
        $data['productImageAdditional'] = [];

        $this->galleryReadHandler->execute($product);

        $mediaUrl = $this->getMediaUrl();

        $gallery = $product->getData('media_gallery');
        if ($gallery && is_array($gallery) && isset($gallery['images'])) {
            $productImageAdditional = [];
            $imageN = 1;

            foreach ($gallery['images'] as $g) {
                if ($g['disabled'] == '0' && $g['media_type'] == 'image' && $g['file'] != '') {
                    $imageUrl = $mediaUrl . "catalog/product" . $g['file'];
                    $data['images'] = $imageUrl;
//                        if ($g['position'] == '1') {
//                            $productThumbnail = $productImage;
//                        }
                    if ($imageN == 1) {
                        $data['productImage1'] = ['url' => $imageUrl, 'file' => $g['file']];
                        $productImageAdditional[] = ['url' => $imageUrl, 'file' => $g['file']];
                    }
                    if ($imageN == 2) {
                        $data['productImage2'] = ['url' => $imageUrl, 'file' => $g['file']];
                        $productImageAdditional[] = ['url' => $imageUrl, 'file' => $g['file']];
                    }
                    if ($imageN == 3) {
                        $data['productImage3'] = ['url' => $imageUrl, 'file' => $g['file']];
                        $productImageAdditional[] = ['url' => $imageUrl, 'file' => $g['file']];
                    }
                    if ($imageN == 4) {
                        $data['productImage4'] = ['url' => $imageUrl, 'file' => $g['file']];
                        $productImageAdditional[] = ['url' => $imageUrl, 'file' => $g['file']];
                    }
                    if ($imageN == 5) {
                        $data['productImage5'] = ['url' => $imageUrl, 'file' => $g['file']];
                        $productImageAdditional[] = ['url' => $imageUrl, 'file' => $g['file']];
                    }
                    if ($imageN == 6) {
                        $data['productImage6'] = ['url' => $imageUrl, 'file' => $g['file']];
                        $productImageAdditional[] = ['url' => $imageUrl, 'file' => $g['file']];
                    }

                    $imageN++;
                }
            }

            $data['productImageAdditional'] = $productImageAdditional;
        }

        return $data;
    }

    /**
     * @return string
     * @throws NoSuchEntityException
     */
    private function getMediaUrl(): string
    {
        if ($this->mediaUrl) {
            return $this->mediaUrl;
        }
        $mediaUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
        if (!empty($mediaUrl)) {
            $mediaUrl = str_replace('/pub', '', $mediaUrl);
        }
        $this->mediaUrl = $mediaUrl;

        return $this->mediaUrl;
    }
}
