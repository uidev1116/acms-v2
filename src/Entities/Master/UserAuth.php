<?php

declare(strict_types=1);

namespace Acms\Plugins\V2\Entities\Master;

/**
 * ユーザー権限
 */
class UserAuth
{
    public const ADMINISTRATOR = 'administrator';
    public const EDITOR = 'editor';
    public const CONTRIBUTOR = 'contributor';
    public const SUBSCRIBER = 'subscriber';
}
