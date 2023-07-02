<?php

namespace Acms\Plugins\V2\Services\Unit\Types;

use Template;

use Acms\Plugins\V2\Services\Unit\UnitInterface;
use Acms\Plugins\V2\Entities\Unit;

/**
 * Googleマップユニット
 */
class GoogleMapUnit implements UnitInterface
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
        [$x, $y] = $this->Unit->getSize();
        $message = str_replace(
            ['"', '<', '>', '&'],
            ['[[:quot:]]', '[[:lt:]]', '[[:gt:]]', '[[:amp:]]'],
            $this->Unit->getField1()
        );
        $vars = [
            'id' => $this->Unit->getId(),
            'type' => $this->Unit->getType(),
            'sort' => $this->Unit->getSort(),
            'align' => $this->Unit->getAlign(),
            'attr' => $this->Unit->getAttr(),
            'group' => $this->Unit->getGroup(),
            'x' => $x,
            'y' => $y,
            'message' => $message,
            'lat' => $this->Unit->getField2(),
            'lng' => $this->Unit->getField3(),
            'zoom' => $this->Unit->getField4(),
            'displaySize' => $this->Unit->getField5()
        ];

        if (!empty($this->Unit->getField6())) {
            [$pitch, $zoom, $heading] = explode(',', $this->Unit->getField7());
            $vars = [
                ...$vars,
                'view' => [
                    'pitch' => $pitch,
                    'zoom' => $zoom,
                    'heading' => $heading
                ]
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
