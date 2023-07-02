<?php

namespace Acms\Plugins\V2\Services\Unit;

use Template;
use Acms\Plugins\V2\Entities\Unit;

/**
 * 必要に応じてユニットごとに実装する
 */
interface UnitInterface
{
    /**
     * テンプレートの組み立て
     *
     * @return array
     */
    public function build(): array;

    /**
     * テンプレートを組み立てるユニットを指定します。
     *
     * @param Unit
     * @return UnitInterface
     */
    public function setUnit(Unit $Unit): UnitInterface;

    /**
     * テンプレートオブジェクトを指定します。
     *
     * @param Template
     * @return UnitInterface
     */
    public function setTpl(Template $Tpl): UnitInterface;

    /**
     * ブロックを指定します。
     *
     * @param array
     * @return UnitInterface
     */
    public function setBlock(array $block): UnitInterface;

    /**
     * URLコンテキストを指定します。
     *
     * @param array
     * @return UnitInterface
     */
    public function setContext(array $context): UnitInterface;

    /**
     * コンフィグを指定します。
     *
     * @param array
     * @return UnitInterface
     */
    public function setConfig(array $config): UnitInterface;
}
