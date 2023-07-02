<?php

namespace Acms\Plugins\V2\Services\Unit\Types;

use Acms\Services\Facades\Storage;
use Template;
use Acms\Plugins\V2\Services\Unit\UnitInterface;
use Acms\Plugins\V2\Entities\Unit;

/**
 * 画像ユニット
 */
class ImageUnit implements UnitInterface
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
        $path = ARCHIVES_DIR . $this->Unit->getField2();
        $sizes = Storage::getImageSize($path);
        $x = isset($sizes[0]) ? $sizes[0] : null;
        $y = isset($sizes[1]) ? $sizes[1] : null;

        $vars = [
            'id' => $this->Unit->getId(),
            'type' => $this->Unit->getType(),
            'sort' => $this->Unit->getSort(),
            'align' => $this->Unit->getAlign(),
            'attr' => $this->Unit->getAttr(),
            'group' => $this->Unit->getGroup(),
            'x' => $x,
            'y' => $y,
            'caption' => $this->Unit->getField1(),
            'path' => $this->Unit->getField2(),
            'link' => $this->Unit->getField3(),
            'alt' => $this->Unit->getField4(),
            'displaySize' => $this->Unit->getField5(),
            'eid' => $this->Unit->getEntry()->getId()
        ];

        $tinyPath = otherSizeImagePath($path, 'tiny');
        $tinySizes = Storage::getImageSize($tinyPath);
        if ($tinySizes) {
            $vars = [
                ...$vars,
                'tinyPath' => $tinyPath,
                'tinyX' => $tinySizes[0],
                'tinyY' => $tinySizes[1]
            ];
        }

        $squarePath = otherSizeImagePath($path, 'square');
        if (Storage::isFile($squarePath)) {
            $vars = [
                ...$vars,
                'squarePath' => $squarePath,
                'squareX' => config('image_size_square'),
                'squareY' => config('image_size_square')
            ];
        }

        $largePath = otherSizeImagePath($path, 'large');
        $largeSizes = Storage::getImageSize($largePath);
        if ($largeSizes) {
            $vars = [
                ...$vars,
                'largePath' => $largePath,
                'largeX' => $largeSizes[0],
                'largeY' => $largeSizes[1],
                ...(!empty($this->config['imageViewer']) ? [
                    'viewer' => $this->config['imageViewer'],
                    'eid' => $this->Unit->getEntry()->getId()
                ] : []),
            ];
        }

        return $vars;
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
}
