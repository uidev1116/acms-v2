<?php

namespace Acms\Plugins\V2\Services\Unit\Types;

use Acms\Services\Facades\Template as Tpl;
use Template;
use Acms\Plugins\V2\Services\Unit\UnitInterface;
use Acms\Plugins\V2\Entities\Unit;

/**
 *  カスタムユニット
 */
class CustomUnit implements UnitInterface
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
        return [
            'id' => $this->Unit->getId(),
            'type' => $this->Unit->getType(),
            'sort' => $this->Unit->getSort(),
            'align' => $this->Unit->getAlign(),
            'attr' => $this->Unit->getAttr(),
            'group' => $this->Unit->getGroup(),
            ...Tpl::buildField(
                acmsUnserialize($this->Unit->getField6()),
                $this->Tpl,
                $this->block
            ),
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
