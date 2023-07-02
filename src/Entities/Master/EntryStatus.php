<?php

declare(strict_types=1);

namespace Acms\Plugins\V2\Entities\Master;

/**
 * エントリーステータス
 */
class EntryStatus
{
    public const OPEN = 'open';
    public const CLOSE = 'close';
    public const DRAFT = 'draft';
    public const TRASH = 'trash';
}
