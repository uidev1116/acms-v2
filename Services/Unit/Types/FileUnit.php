<?php

namespace Acms\Plugins\V2\Services\Unit\Types;

use Acms\Services\Facades\Storage;
use Template;

use Acms\Plugins\V2\Services\Unit\UnitInterface;
use Acms\Plugins\V2\Entities\Unit;

/**
 * ファイルユニット
 */
class FileUnit implements UnitInterface
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
        $vars = [
            'id' => $this->Unit->getId(),
            'type' => $this->Unit->getType(),
            'sort' => $this->Unit->getSort(),
            'align' => $this->Unit->getAlign(),
            'attr' => $this->Unit->getAttr(),
            'group' => $this->Unit->getGroup(),
            'size' => $this->Unit->getSize(),
            'caption' => $this->Unit->getField1(),
            'path' => $path
        ];

        $iconPath = ite(pathinfo($path), 'extension');
        if (Storage::exists($iconPath)) {
            $x = 70;
            $y = 81;
            if (config('file_icon_size') === 'dynamic') {
                $iconSizes = Storage::getImageSize($iconPath);
                $x = isset($iconSizes[0]) ? $iconSizes[0] : $x;
                $y = isset($iconSizes[1]) ? $iconSizes[1] : $y;
            }
            $vars = [
                ...$vars,
                'iconPath' => $iconPath,
                'x' => $x,
                'y' => $y
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
