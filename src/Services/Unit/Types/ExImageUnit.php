<?php

namespace Acms\Plugins\V2\Services\Unit\Types;

use Template;
use Acms\Plugins\V2\Services\Unit\UnitInterface;
use Acms\Plugins\V2\Entities\Unit;

/**
 * 画像URLユニット
 */
class ExImageUnit implements UnitInterface
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
        [$x, $y] = explode('x', $this->Unit->getSize());
        return [
            'id' => $this->Unit->getId(),
            'type' => $this->Unit->getType(),
            'align' => $this->Unit->getAlign(),
            'sort' => $this->Unit->getSort(),
            'attr' => $this->Unit->getAttr(),
            'group' => $this->Unit->getGroup(),
            'x' => $x,
            'y' => $y,
            'caption' => $this->Unit->getField1(),
            'path' => $this->Unit->getField2(),
            'largePath' => $this->Unit->getField3(),
            'link' => $this->Unit->getField4(),
            'alt' => $this->Unit->getField5(),
            'displaySize' => $this->Unit->getField6(),
            ...(!empty($this->config['imageViewer']) ? [
                'viewer' => $this->config['imageViewer'],
                'eid' => $this->Unit->getEntry()->getId()
            ] : []),
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
}
