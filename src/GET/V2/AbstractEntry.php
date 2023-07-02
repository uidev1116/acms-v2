<?php

namespace Acms\Plugins\V2\GET\V2;

use ACMS_GET;
use ACMS_Corrector;
use ACMS_RAM;
use Template;
use Acms\Services\Facades\Application as App;
use Acms\Services\Facades\Database as DB;
use Acms\Services\Facades\Template as Tpl;
use Acms\Plugins\V2\Repositories\EntryRepository;

// TODO: リビジョン対応
// TODO: コメント対応
abstract class AbstractEntry extends ACMS_GET
{
    public string|null $order;
    public string|null $page;

    protected bool $showField;

    protected array $config;
    protected string $amountQuery;

    public $_axis = [ // phpcs:ignore
        'bid' => 'self',
        'cid' => 'self',
    ];

    /**
     * @var EntryRepository
     */
    protected EntryRepository $EntryRepository;

    /**
     * 初期化処理
     *
     * @param \Acms\Services\Container
     */
    protected function init(\Acms\Services\Container $container)
    {
        $this->EntryRepository = $container->make('v2.repositry.entry');
    }

    /**
     * コンフィグの取得
     *
     * @return array
     */
    protected function initVars()
    {
        return [
            'order' => [
                $this->order ? $this->order : 'datetime-desc',
                'id-desc',
            ],
            'orderFieldName'        => '',
            'noNarrowDownSort'      => 'off',
            'limit'                 => 5,
            'offset'                => 0,
            'indexing'              => 'on',
            'subCategory'           => 'on',
            'secret'                => 'off',
            'notfound'              => 'on',
            'notfoundStatus404'     => 'off',
            'newtime'               => 259200,
            'hiddenCurrentEntry'    => 'off',
            'hiddenPrivateEntry'    => 'off',
            'summaryRange'          => null,
            'showAllIndex'          => 'off',

            'pagerOn'               => 'off',
            'pagerDelta'            => 3,
            'pagerCurAttr'          => 'class="cur"',

            'serialEntryOn'          => 'on',
            'serialEntryIgnoreCategory' => 'off',

            'microPager'            => 'on',
            'microPagerDelta'       => 1,
            'microPagerCurAttr'     => 'class="cur"',

            'mainImageOn'           => 'on',
            'noimage'               => 'on',
            'imageX'                => 100,
            'imageY'                => 100,
            'imageTrim'             => 'off',
            'imageZoom'             => 'off',
            'imageCenter'           => 'off',

            'entryFieldOn'          => 'on',
            'relatedEntryOn'        => 'off',
            'categoryInfoOn'        => 'on',
            'categoryFieldOn'       => 'off',
            'userInfoOn'            => 'off',
            'userFieldOn'           => 'off',
            'blogInfoOn'            => 'off',
            'blogFieldOn'           => 'off',
            'unitInfoOn'            => 'on',
            'detailDateOn'          => 'on',
            'fullTextOn'            => 'on',
            'fulltextWidth'         => 300,
            'fulltextMarker'        => '...',
            'tagOn'                 => 'on',
            'loopClass'             => '',
            'imageViewer'           => '',
        ];
    }

    /**
     * コンフィグのセット
     *
     * @return bool
     */
    protected function setConfig()
    {
        $this->config = $this->initVars();
        if ($this->config === false) {
            return false;
        }
        return true;
    }

    public function get()
    {
        $this->init(App::getInstance());
        if (!$this->setConfig()) {
            return '';
        }

        $Tpl = new Template($this->tpl, new ACMS_Corrector());

        $Entries = $this->findEntries();

        if ($this->config['notfound'] === 'on' && empty($Entries)) {
            return $Tpl->render([
                ...($this->isModuleFieldEnabled() ? [
                    'moduleField' => $this->buildModuleField($Tpl, ['moduleField'])
                ] : []),
                'notFound' => $this->buildNotFound($Tpl),
                ...$this->getRootVars()
            ]);
            ;
        }

        if (
            1 &&
            is_int($this->eid) && // 詳細ページの場合
            $this->config['serialEntryOn'] === 'on' &&
            $this->config['order'][0] !== 'random'
        ) {
            [$PrevEntry, $NextEntry] = $this->findSerialEntries($Entries[0]);
        }

        return $Tpl->render([
            ...($this->isModuleFieldEnabled() ? [
                'moduleField' => $this->buildModuleField($Tpl, ['moduleField'])
            ] : []),
            'entry' => array_map(
                function (\Acms\Plugins\V2\Entities\Entry $Entry) use ($Tpl) {
                    return [
                        'loopClass' => $this->config['loopClass'],
                        ...$this->buildEntry($Entry, $Tpl, ['entry:loop']),
                        ...(is_int($this->eid) && $this->config['microPager'] === 'on' ? [
                            'microPager' => $this->buildMicroPagenation($Entry)
                        ] : [])
                    ];
                },
                $Entries
            ),
            ...(!is_int($this->eid) && // 詳細ページの場合
                $this->config['order'][0] !== 'random' &&
                $this->config['pagerOn'] === 'on'
                ? ['pager' => $this->buildFullSpecPagenation()]
                : []
            ),
            ...(is_int($this->eid) && // 詳細ページの場合
                $this->config['serialEntryOn'] === 'on' &&
                $this->config['order'][0] !== 'random'
                ? $this->buildSerialEntry($PrevEntry, $NextEntry, $Tpl)
                : []
            ),
            ...$this->getRootVars()
        ]);
    }

    /**
     * エントリーデータの取得
     *
     * @return array
     */
    protected function findEntries(): array
    {
        [$Entries, $amoutQuery] = $this->EntryRepository->find(
            $this->context(),
            $this->axis(),
            $this->config
        );

        $this->amountQuery = $amoutQuery;

        return $Entries;
    }

    /**
     * 前後エントリーデータの取得
     *
     * @return \Acms\Plugins\V2\Entities\Entry
     */
    protected function findSerialEntries(\Acms\Plugins\V2\Entities\Entry $Entry)
    {
        return $this->EntryRepository->findSerialEntries(
            $Entry,
            $this->context(),
            $this->axis(),
            $this->config
        );
    }

    /**
     * エントリーの組み立て
     *
     * @param \Acms\Plugins\V2\Entities\Entry $Entry
     * @param Template $Tpl
     * @param array $block
     * @param array $config
     *
     * @return array
     */
    protected function buildEntry(
        \Acms\Plugins\V2\Entities\Entry $Entry,
        Template $Tpl,
        array $block
    ): array {
        return Tpl::buildEntry($Entry, $Tpl, $block, $this->config, $this->context());
    }

    /**
     * ページャーの組み立て
     *
     * @param int $page ページ数
     * @param int $limit 1ページの件数
     * @param int $amount 総数
     * @param int $delta 前後ページ数
     * @param string $curAttr
     * @param array $Q
     *
     * @return array
     */
    protected function buildPagenation($page, $limit, $amount, $delta, $curAttr, $Q = [])
    {
        return Tpl::buildPagenation($page, $limit, $amount, $delta, $curAttr, $Q);
    }

    /**
     * フルスペックページャーの組み立て
     *
     * @return array
     */
    protected function buildFullspecPagenation()
    {
        $itemsAmount = intval(DB::query($this->amountQuery, 'one'));
        $itemsAmount -= $this->config['offset'];
        return $this->buildPagenation(
            intval($this->page),
            $this->config['limit'],
            $itemsAmount,
            $this->config['pagerDelta'],
            $this->config['pagerCurAttr'],
        );
    }

    /**
     * マイクロページャーの組み立て
     *
     * @param \Acms\Plugins\V2\Entities\Entry $Entry
     *
     * @return array
     */
    protected function buildMicroPagenation(\Acms\Plugins\V2\Entities\Entry $Entry)
    {
        return $this->buildPagenation(
            intval($this->page),
            1,
            $Entry->getMicroPageAmount(),
            $this->config['microPagerDelta'],
            $this->config['microPagerCurAttr'],
        );
    }

    /**
     * 前後エントリーのテンプレート組み立て
     *
     * @param \Acms\Plugins\V2\Entities\Entry|null $PrevEntry
     * @param \Acms\Plugins\V2\Entities\Entry|null $NextEntry
     * @param Template $Tpl
     *
     * @return array
     */
    protected function buildSerialEntry(
        ?\Acms\Plugins\V2\Entities\Entry $PrevEntry,
        ?\Acms\Plugins\V2\Entities\Entry $NextEntry,
        Template $Tpl
    ): array {
        return Tpl::buildSerialEntry(
            $PrevEntry,
            $NextEntry,
            $Tpl,
            $this->config,
            $this->context()
        );
    }

    /**
     * NotFound時のテンプレート組み立て
     *
     * @return \stdClass
     */
    public function buildNotFound(): \stdClass
    {
        if (isset($this->config['notfoundStatus404']) && $this->config['notfoundStatus404'] === 'on') {
            httpStatusCode('404 Not Found');
        }
        return new \stdClass();
    }

    /**
     * モジュールフィールドの組み立てを行うか
     *
     * @return bool
     */
    protected function isModuleFieldEnabled(): bool
    {
        return !is_null($this->mid) && is_int($this->mid) && $this->showField === true;
    }

    /**
     * モジュールフィールドの組み立て
     *
     * @param Template &$Tpl
     *
     * @return array
     */
    public function buildModuleField(&$Tpl, $block = [])
    {
        return Tpl::buildField(loadModuleField($this->mid), $Tpl, $block);
    }

    /**
     * ルート変数の取得
     *
     * @return array
     */
    protected function getRootVars()
    {
        $blogName   = ACMS_RAM::blogName($this->bid);
        $vars = [
            'indexUrl'  => acmsLink(array(
                'bid' => $this->bid,
                'cid' => $this->cid,
            )),
            'indexBlogName' => $blogName,
            'blogName'      => $blogName,
            'blogCode'      => ACMS_RAM::blogCode($this->bid),
            'blogUrl'       => acmsLink(array(
                'bid' => $this->bid,
            )),
        ];
        if (!empty($this->cid)) {
            $categoryName = ACMS_RAM::categoryName($this->cid);
            $vars['indexCategoryName']  = $categoryName;
            $vars['categoryName']       = $categoryName;
            $vars['categoryCode']       = ACMS_RAM::categoryCode($this->cid);
            $vars['categoryUrl']        = acmsLink(array(
                'bid' => $this->bid,
                'cid' => $this->cid,
            ));
        }
        return $vars;
    }

    protected function context(): array
    {
        return [
            'bid' => $this->bid,
            'uid' => $this->bid,
            'cid' => $this->cid,
            'eid' => $this->eid,
            'keyword' => $this->keyword,
            'tags' => $this->tags,
            'field' => $this->Field,
            'start' => $this->start,
            'end' => $this->end,
            'page' => intval($this->page),
            'order' => $this->order
        ];
    }

    protected function axis(): array
    {
        return [
            'bid' => $this->blogAxis(),
            'cid' => $this->categoryAxis()
        ];
    }
}
