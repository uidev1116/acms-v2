<?php

declare(strict_types=1);

namespace Acms\Plugins\V2\Entities;

/**
 * 関連エントリーグループ
 */
class RelatedEntryGroup
{
    /**
     * 関連タイプ
     */
    private string $type;


    /**
     * 関連エントリー
     *
     * @var Entry[]
     */
    private array $Entries = [];


    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * @return Entry[]
     */
    public function getEntries(): array
    {
        return $this->Entries;
    }

    /**
     * @param Entry[]
     *
     * @return void
     */
    public function setEntries(array $Entries): void
    {
        $this->Entries = $Entries;
    }
}
