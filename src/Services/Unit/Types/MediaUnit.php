<?php

namespace Acms\Plugins\V2\Services\Unit\Types;

use DOMDocument;
use DOMElement;
use Acms\Services\Facades\Template as Tpl;
use Acms\Services\Facades\Media as MediaService;
use Acms\Services\Facades\Storage;
use Template;
use Acms\Plugins\V2\Services\Unit\UnitInterface;
use Acms\Plugins\V2\Entities\Media;
use Acms\Plugins\V2\Entities\Unit;

/**
 * メディアユニット
 */
class MediaUnit implements UnitInterface
{
    protected Unit $Unit;

    protected Template $Tpl;

    protected array $block;

    /**
     * @var array{
     *  bid: string|int|null,
     *  uid: string|int|null,
     *  cid: string|int|null,
     *  eid: stirng|int|null,
     *  keyword: string|null,
     *  tags: string[],
     *  field: \Field,
     *  start: string|null,
     *  end: string|null
     *  page: int,
     *  order: string|null
     * }
     */
    protected array $context;

    protected array $config;

    /**
     * {@inheritdoc}
     */
    public function build(): array
    {
        /** @var Media $Media */
        $Media = $this->Unit->getField1();
        return [
            'id' => $this->Unit->getId(),
            'type' => $this->Unit->getType(),
            'sort' => $this->Unit->getSort(),
            'align' => $this->Unit->getAlign(),
            'attr' => $this->Unit->getAttr(),
            'group' => $this->Unit->getGroup(),
            'displaySize' => $this->Unit->getField6(),
            'media' => [
                ...Tpl::buildMedia($Media, $this->Tpl, ['media', ...$this->block]),
                ...(!empty($this->Unit->getField2()) ? [
                    'caption' => $this->Unit->getField2()
                ] : []),
                ...(!empty($this->Unit->getField3()) ? [
                    'alt' => $this->Unit->getField3()
                ] : []),
                ...(MediaService::isFile($Media->getType()) ? $this->getIcon() : []),
                ...(!MediaService::isFile($Media->getType()) ? [
                    ...$this->getSize(),
                ] : []),
                'link' => $this->getLink(),
                ...(!MediaService::isFile($Media->getType()) && !empty($this->config['imageViewer']) ? [
                    'viewer' => $this->config['imageViewer'],
                    'eid' => $this->Unit->getEntry()->getId()
                ] : []),
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function setUnit(Unit $Unit): UnitInterface
    {
        $this->Unit = $Unit;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setTpl(Template $Tpl): UnitInterface
    {
        $this->Tpl = $Tpl;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setBlock(array $block): UnitInterface
    {
        $this->block = $block;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setContext(array $context): UnitInterface
    {
        $this->context = $context;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setConfig(array $config): UnitInterface
    {
        $this->config = $config;

        return $this;
    }

    /**
     * リンクの取得
     *
     * @return float[]
     */
    private function getLink(): string
    {
        /** @var Media $Media */
        $Media = $this->Unit->getField1();

        if (MediaService::isImageFile($Media->getType()) || MediaService::isSvgFile($Media->getType())) {
            $link = $this->Unit->getField7() ?: $Media->getLink();
            $link = setGlobalVars($link);

            if ($this->Unit->getField4() !== 'no') {
                $link = MediaService::getImagePermalink($Media->getPath());
            }
        } elseif (MediaService::isFile($Media->getType())) {
            if ($Media->getStatus()) {
                $link = MediaService::getFileOldPermalink($Media->getPath(), false);
            } else {
                $link = MediaService::getFilePermalink($Media->getId(), false);
            }
        }

        return $link;
    }

    /**
     * サイズの取得
     *
     * @return float[]
     */
    private function getSize(): array
    {
        /** @var Media $Media */
        $Media = $this->Unit->getField1();

        if (MediaService::isFile($Media->getType())) {
            return [];
        }

        $mediaX = $Media->getWidth();
        $mediaY = $Media->getHeight();
        if (strpos($this->Unit->getSize(), 'x') !== false) {
            list($unitX, $unitY) = explode('x', $this->Unit->getSize());
            if ($mediaX >= $unitX && $mediaY >= $unitY) {
                $x = $unitX;
                $y = $unitY;
            } else {
                $x = $mediaX;
                $y = $mediaY;
            }
        } elseif ($mediaX > 0 && $mediaY > 0) {
            $unitX = $this->Unit->getSize() ?: 0;
            $unitY = intval(intval($unitX) * ($mediaY / $mediaX));
            if (!empty($unitX) && !empty($unitY) && $mediaX >= $unitX && $mediaY >= $unitY) {
                $x = $unitX;
                $y = $unitY;
            } else {
                $x = $mediaX;
                $y = $mediaY;
            }
        } elseif (MediaService::isSvgFile($Media->getType())) {
            $x = $this->Unit->getSize();
            $y = $this->Unit->getSize();

            $doc = new DOMDocument();
            if ($doc->loadXML(Storage::get(MEDIA_LIBRARY_DIR . $Media->getPath()))) {
                $svg = $doc->getElementsByTagName('svg');
                /** @var DomElement $item */
                $item = $svg->item(0);
                $width = intval($item->getAttribute('width'));
                $height = intval($item->getAttribute('height'));
                if (empty($width) || empty($height)) {
                    if ($viewBox = $item->getAttribute('viewBox')) {
                        $viewBox = explode(' ', $viewBox);
                        $width = intval($viewBox[2]);
                        $height = intval($viewBox[3]);
                    }
                }
                if ($width > 0 && $height > 0) {
                    $y = intval(intval($x) * ($height / $width));
                }
            }
        } else {
            $x = $this->Unit->getSize();
            $y = null;
        }

        return [
            'x' => $x,
            'y' => $y
        ];
    }

    /**
     * アイコンの取得
     *
     * @return array
     */
    private function getIcon(): array
    {
        /** @var Media $Media */
        $Media = $this->Unit->getField1();

        if (MediaService::isFile($Media->getType()) === false) {
            return [];
        }

        $useIcon = $this->Unit->getField5();

        $vars = [
            'useIcon' => $useIcon
        ];

        if ($useIcon !== 'yes') {
            return $vars;
        }

        $x = 70;
        $y = 81;
        $icon = pathIcon($Media->getExtension());

        if (config('file_icon_size') === 'dynamic') {
            $xy = Storage::getImageSize($icon);
            $x = isset($xy[0]) ? $xy[0] : $x;
            $y = isset($xy[1]) ? $xy[1] : $y;
        }

        return [
            ...$vars,
            'icon' => $icon,
            'x' => $x,
            'y' => $y
        ];
    }
}
