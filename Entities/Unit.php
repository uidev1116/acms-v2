<?php

declare(strict_types=1);

namespace Acms\Plugins\V2\Entities;

use Acms\Plugins\V2\Entities\Master\UnitAlign;

/**
 * ユニット
 */
class Unit
{
    public function isHidden()
    {
        return $this->aligin === UnitAlign::HIDDEN;
    }

    public function isClearGroup()
    {
        return $this->group === config('unit_group_clear', 'acms-column-clear');
    }

    public function getSpecifiedType()
    {
        return detectUnitTypeSpecifier($this->type);
    }

    /**
     * ID
     */
    private int $id;

    /**
     * ソート番号
     */
    private int $sort;

    /**
     * 配置
     */
    private string $aligin;

    /**
     * タイプ
     */
    private string $type;

    /**
     * 属性
     */
    private string $attr;

    /**
     * ユニットグループ
     */
    private string $group;

    /**
     * サイズ
     */
    private float|string $size;

    /**
     * フィールド1
     */
    private mixed $field1;

    /**
     * フィールド2
     */
    private mixed $field2;

    /**
     * フィールド3
     */
    private mixed $field3;

    /**
     * フィールド4
     */
    private mixed $field4;

    /**
     * フィールド5
     */
    private mixed $field5;

    /**
     * フィールド6
     */
    private mixed $field6;

    /**
     * フィールド7
     */
    private mixed $field7;

    /**
     * フィールド8
     */
    private mixed $field8;

    /**
     * エントリー
     */
    private Entry $Entry;


    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getSort(): int
    {
        return $this->sort;
    }

    public function setSort(int $sort): void
    {
        $this->sort = $sort;
    }

    public function getAlign(): string
    {
        return $this->aligin;
    }

    public function setAlign(string $aligin): void
    {
        $this->aligin = $aligin;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getAttr(): string
    {
        return $this->attr;
    }

    public function setAtrr(string $attr): void
    {
        $this->attr = $attr;
    }

    public function getGroup(): string
    {
        return $this->group;
    }

    public function setGroup(string $group): void
    {
        $this->group = $group;
    }

    public function getSize(): float|string
    {
        return $this->size;
    }

    public function setSize(float|string $size): void
    {
        $this->size = $size;
    }

    public function getField1(): mixed
    {
        return $this->field1;
    }

    public function setField1(mixed $field1): void
    {
        $this->field1 = $field1;
    }

    public function getField2(): mixed
    {
        return $this->field2;
    }

    public function setField2(mixed $field2): void
    {
        $this->field2 = $field2;
    }

    public function getField3(): mixed
    {
        return $this->field3;
    }

    public function setField3(mixed $field3): void
    {
        $this->field3 = $field3;
    }

    public function getField4(): mixed
    {
        return $this->field4;
    }

    public function setField4(mixed $field4): void
    {
        $this->field4 = $field4;
    }

    public function getField5(): mixed
    {
        return $this->field5;
    }

    public function setField5(mixed $field5): void
    {
        $this->field5 = $field5;
    }

    public function getField6(): mixed
    {
        return $this->field6;
    }

    public function setField6(mixed $field6): void
    {
        $this->field6 = $field6;
    }

    public function getField7(): mixed
    {
        return $this->field7;
    }

    public function setField7(mixed $field7): void
    {
        $this->field7 = $field7;
    }

    public function getField8(): mixed
    {
        return $this->field8;
    }

    public function setField8(mixed $field8): void
    {
        $this->field8 = $field8;
    }

    public function getEntry(): Entry
    {
        return $this->Entry;
    }

    public function setEntry(Entry $Entry): void
    {
        $this->Entry = $Entry;
    }
}
