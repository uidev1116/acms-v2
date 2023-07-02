<?php

namespace Acms\Plugins\V2\Services\Template;

use Template;
use Acms\Services\Template\Helper as Base;

use Acms\Plugins\V2\Services\Unit\UnitFactory;

use Acms\Plugins\V2\Entities\Master\EntryApproval;
use Acms\Plugins\V2\Entities\Master\UnitType;
use Acms\Plugins\V2\Entities\Blog;
use Acms\Plugins\V2\Entities\Category;
use Acms\Plugins\V2\Entities\Entry;
use Acms\Plugins\V2\Entities\Geo;
use Acms\Plugins\V2\Entities\Media;
use Acms\Plugins\V2\Entities\RelatedEntryGroup;
use Acms\Plugins\V2\Entities\Tag;
use Acms\Plugins\V2\Entities\Unit;
use Acms\Plugins\V2\Entities\User;

class Helper extends Base
{
    /**
     * エントリーの組み立て
     *
     * @param Entry $Entry
     * @param Template $Tpl
     * @param array $block
     * @param array $config
     * @param array{
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
     * } $context
     *
     * @return array
     */
    public function buildEntry(
        Entry $Entry,
        Template $Tpl,
        array $block,
        array $config,
        array $context = []
    ): array {
        return [
            'id' => $Entry->getId(),
            'code' => $Entry->getCode(),
            'sort' => $Entry->getSort(),
            'csort' => $Entry->getCategorySort(),
            'usort' => $Entry->getUserSort(),
            'status' => $Entry->getStatus(),
            'title' => $Entry->getPrefixedTitle(),
            'url' => $Entry->getUrl(),
            'parmalink' => $Entry->getParmalink(),
            'isNew' => requestTime() <= ($Entry->getDatetime()->getTimestamp() + $config['newtime']),
            ...(!is_null($Entry->getGeo()) ? $this->buildGeo($Entry->getGeo()) : []),
            ...$this->buildField(
                $Entry->getField(),
                $Tpl,
                $block
            ),
            ...$this->buildDate(
                $Entry->getDatetime()->format('Y-m-d H:i:s'),
                $Tpl,
                $block
            ),
            ...($config['detailDateOn'] === 'on' ? [
                ...$this->buildDate(
                    $Entry->getUpdatedDatetime()->format('Y-m-d H:i:s'),
                    $Tpl,
                    $block,
                    'udate#'
                ),
                ...$this->buildDate(
                    $Entry->getPostedDatetime()->format('Y-m-d H:i:s'),
                    $Tpl,
                    $block,
                    'pdate#'
                ),
                ...$this->buildDate(
                    $Entry->getStartDatetime()->format('Y-m-d H:i:s'),
                    $Tpl,
                    $block,
                    'sdate#'
                ),
                ...$this->buildDate(
                    $Entry->getEndDatetime()->format('Y-m-d H:i:s'),
                    $Tpl,
                    $block,
                    'edate#'
                ),
            ] : []),
            ...($config['mainImageOn'] === 'on'
                ?   [
                    'mainImage' => [
                        ...(!is_null($Entry->getPrimaryImage())
                            ? $this->buildPrimaryImage(
                                $Entry->getPrimaryImage(),
                                $Tpl,
                                ['mainImage', ...$block],
                                $config,
                                $context
                            )
                            : []
                        )
                    ]
                ] : []
            ),
            ...($config['relatedEntryOn'] === 'on' ? [
                'relatedEntry' => $this->buildRelatedEntryGroups(
                    $Entry->getRelatedEntryGroups(),
                    $Tpl,
                    ['relatedEntry', ...$block],
                    $config,
                    $context
                )
            ] : []),
            ...($config['tagOn'] === 'on' ? [
                'tag' => $this->buildTags($Entry->getTags())
            ] : []),
            ...($config['subCategory'] === 'on' ? [
                'sub_category' => $this->buildSubCategories(
                    $Entry->getSubCategories(),
                    $Tpl,
                    ['sub_category:loop', ...$block],
                    $config
                )
            ] : []),
            ...($config['fullTextOn'] === 'on' ? [
                'summary' => $this->trimSummary($Entry->getSummary(), $config)
            ] : []),
            ...($config['userInfoOn'] === 'on' ? [
                'user' => $this->buildUser(
                    $Entry->getUser(),
                    $Tpl,
                    ['user', ...$block],
                    $config
                ),
                'lastUpdateUser' => $this->buildUser(
                    $Entry->getLastUpdateUser(),
                    $Tpl,
                    ['lastUpdateUser', ...$block],
                    $config
                )
            ] : []),
            ...($config['categoryInfoOn'] === 'on' && !is_null($Entry->getCategory()) ? [
                'category' => $this->buildCategory(
                    $Entry->getCategory(),
                    $Tpl,
                    ['category', ...$block],
                    $config
                )
            ] : []),
            ...($config['blogInfoOn'] === 'on' ? [
                'blog' => $this->buildBlog(
                    $Entry->getBlog(),
                    $Tpl,
                    ['blog', ...$block],
                    $config
                )
            ] : []),
            ...($config['unitInfoOn'] === 'on' ? [
                'unit' => $this->buildUnits(
                    $this->filterUnit($Entry, $config, $context),
                    $Tpl,
                    ['unit', ...$block],
                    $config,
                    $context
                ),
                ...($Entry->hasContinue() ? [
                    'continueUrl' => $Entry->getUrl(),
                    'continueName' => $Entry->getTitle()
                ] : []),
            ] : []),
        ];
    }

    /**
     * 表示するユニットを絞り込む
     *
     * @param Entry $Entry
     * @param array $config
     * @param array{
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
     * } $context
     *
     * @return Unit[]
     */
    private function filterUnit(Entry $Entry, array $config, array $context): array
    {
        $Units = $Entry->getUnits();

        if (VIEW === 'entry') {
            if ($config['microPager'] === 'on') {
                $Units = $Entry->getUnitsSplitedByPage()[$context['page'] - 1];
            }
        } else {
            // filter by summaryRange
            $summaryRange = $Entry->getSummaryRange();
            if (is_numeric($config['summaryRange'])) {
                $summaryRange = intval($config['summaryRange']);
            }
            if ($config['showAllIndex'] === 'on') {
                $summaryRange = null;
            }

            if (!is_null($summaryRange)) {
                $Units = array_values(
                    array_filter(
                        $Units,
                        function (int $i) use ($summaryRange) {
                            return $i < $summaryRange;
                        },
                        ARRAY_FILTER_USE_KEY
                    )
                );
            }
        }

        // filter hidden uint
        if (0
            || !sessionWithContribution(BID)
            || !roleEntryUpdateAuthorization(BID, [
                'entry_id' => $Entry->getId(),
                'entry_user_id' => $Entry->getUser()->getId()
            ])
            || config('entry_edit_inplace_enable') !== 'on'
            || config('entry_edit_inplace') !== 'on'
            || (enableApproval() && !sessionWithApprovalAdministrator())
            || $Entry->getApproval() === EntryApproval::PRE_APPROVAL
            || VIEW !== 'entry'
        ) {
            $Units = array_values(
                array_filter(
                    $Units,
                    function (Unit $Unit) {
                        return !$Unit->isHidden();
                    }
                )
            );
        }

        return $Units;
    }

    /**
     * 概要文の丸め込み
     *
     * @param  string $summary
     * @param array $config
     *
     * @return string
     */
    public function trimSummary(string $summary, array $config): string
    {
        if (
            0 &&
            empty($summary) ||
            !isset($config['fulltextWidth']) ||
            empty($config['fulltextWidth'])
        ) {
            return $summary;
        }

        $width = intval($config['fulltextWidth']);
        $marker = isset($config['fulltextMarker']) ? $config['fulltextMarker'] : '';
        return mb_strimwidth($summary, 0, $width, $marker, 'UTF-8');
    }

    /**
     * ユーザーの組み立て
     *
     * @param User $User
     * @param Template $Tpl
     * @param array $block
     * @param array $config
     *
     * @return array
     */
    public function buildUser(User $User, Template $Tpl, array $block, array $config): array
    {
        return [
            'id' => $User->getId(),
            'code' => $User->getCode(),
            'status' => $User->getStatus(),
            'sort' => $User->getSort(),
            'name' => $User->getName(),
            'mail' => $User->getMail(),
            'url' => $User->getUrl(),
            'auth' => $User->getAuth(),
            ...$this->buildDate(
                $User->getGeneratedDatetime()->format('Y-m-d H:i:s'),
                $Tpl,
                $block,
                'date#'
            ),
            ...$this->buildDate(
                $User->getUpdatedDatetime()->format('Y-m-d H:i:s'),
                $Tpl,
                $block,
                'udate#'
            ),
            'indexing' => $User->getIndexing(),
            'icon' => $User->getIcon(),
            'largeIcon' => $User->getLargeIcon(),
            ...($config['userFieldOn'] === 'on' ? $this->buildField(
                $User->getField(),
                $Tpl,
                $block
            ) : []),
            'blog' => $this->buildBlog(
                $User->getBlog(),
                $Tpl,
                ['blog', ...$block],
                $config
            )
        ];
    }

    /**
     * カテゴリーの組み立て
     *
     * @param Category $Category
     * @param Template $Tpl
     * @param array $block
     * @param array $config
     *
     * @return array
     */
    public function buildCategory(Category $Category, Template $Tpl, array $block, array $config): array
    {
        return [
            'id' => $Category->getId(),
            'code' => $Category->getCode(),
            'status' => $Category->getStatus(),
            'sort' => $Category->getSort(),
            'name' => $Category->getName(),
            'pcid' => $Category->getCategoryParent(),
            'scope' => $Category->getScope(),
            'indexing' => $Category->getIndexing(),
            'url' => $Category->getUrl(),
            ...($config['categoryFieldOn'] === 'on' ? $this->buildField(
                $Category->getField(),
                $Tpl,
                $block
            ) : []),
            'blog' => $this->buildBlog(
                $Category->getBlog(),
                $Tpl,
                ['blog', ...$block],
                $config
            )
        ];
    }

    /**
     * ブログの組み立て
     *
     * @param Blog $Blog
     * @param Template $Tpl
     * @param array $block
     * @param array $config
     *
     * @return array
     */
    public function buildBlog(Blog $Blog, Template $Tpl, array $block, array $config): array
    {
        return [
            'id' => $Blog->getId(),
            'code' => $Blog->getCode(),
            'status' => $Blog->getStatus(),
            'sort' => $Blog->getSort(),
            'name' => $Blog->getName(),
            'pbid' => $Blog->getBlogParent(),
            'domain' => $Blog->getDomain(),
            'indexing' => $Blog->getIndexing(),
            'url' => $Blog->getUrl(),
            ...$this->buildDate(
                $Blog->getGeneratedDatetime()->format('Y-m-d H:i:s'),
                $Tpl,
                $block,
                'date#'
            ),
            ...($config['blogFieldOn'] === 'on' ? $this->buildField(
                $Blog->getField(),
                $Tpl,
                $block
            ) : [])
        ];
    }

    /**
     * 位置情報の組み立て
     *
     * @param Geo $Geo
     *
     * @return array
     */
    public function buildGeo(Geo $Geo): array
    {
        return [
            'geo_lat' => $Geo->getLatitude(),
            'geo_lng' => $Geo->getLongitude(),
            'get_zoom' => $Geo->getZoom()
        ];
    }

    /**
     * サブカテゴリーの組み立て
     *
     * @param Category[] $SubCategories
     * @param Template $Tpl
     * @param array $block
     * @param array $config
     *
     * @return array
     */
    public function buildSubCategories(array $SubCategories, Template $Tpl, array $block, array $config): array
    {
        return array_map(
            function (Category $SubCategory) use ($Tpl, $block, $config) {
                return $this->buildCategory(
                    $SubCategory,
                    $Tpl,
                    $block,
                    $config
                );
            },
            $SubCategories
        );
    }

    /**
     * タグの組み立て
     *
     * @param Tag[] $Tags
     *
     * @return array
     */
    public function buildTags(array $Tags): array
    {
        return array_map(
            function (Tag $Tag) {
                return [
                    'name' => $Tag->getName(),
                    'url' => $Tag->getUrl()
                ];
            },
            $Tags
        );
    }

    /**
     * 関連エントリーグループの組み立て
     *
     * @param RelatedEntryGroup[] $RelatedEntryGroups
     * @param Template $Tpl
     * @param array $block
     * @param array $config
     * @param array{
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
     * } $context
     *
     * @return array
     */
    public function buildRelatedEntryGroups(
        array $RelatedEntryGroup,
        Template $Tpl,
        array $block,
        array $config,
        array $context
    ): array {
        $config = [
            ...$config,
            'relatedEntryOn' => 'off',
            'fullTextOn' => 'off',
            'unitInfoOn' => 'off'
        ];
        return array_reduce(
            $RelatedEntryGroup,
            function (
                array $array,
                RelatedEntryGroup $RelatedEntryGroup
            ) use (
                $Tpl,
                $block,
                $config,
                $context
            ) {
                return [
                    ...$array,
                    $RelatedEntryGroup->getType() => array_map(
                        function (
                            Entry $RelatedEntry
                        ) use (
                            $Tpl,
                            $RelatedEntryGroup,
                            $block,
                            $config,
                            $context
                        ) {
                            return $this->buildEntry(
                                $RelatedEntry,
                                $Tpl,
                                [$RelatedEntryGroup->getType(), ...$block],
                                $config,
                                $context
                            );
                        },
                        $RelatedEntryGroup->getEntries()
                    )
                ];
            },
            []
        );
    }

    /**
     * ユニットの組み立て
     *
     * @param Unit[] $Units
     * @param Template $Tpl
     * @param array $block
     * @param array $config
     * @param array{
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
     * } $context
     *
     * @return array
     */
    public function buildUnit(
        Unit $UnitEntity,
        Template $Tpl,
        array $block,
        array $config,
        array $context
    ): array {
        $Unit = UnitFactory::singleton()->get($UnitEntity->getSpecifiedType());
        $Unit->setUnit($UnitEntity);
        $Unit->setTpl($Tpl);
        $Unit->setBlock($block);
        $Unit->setConfig($config);
        $Unit->setContext($context);
        return $Unit->build();
    }

    /**
     * ユニットの組み立て
     *
     * @param Unit[] $Units
     * @param Template $Tpl
     * @param array $block
     * @param int $summaryRange
     * @param bool $isDisplayHiddenUnit
     * @param array $config
     * @param array{
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
     * } $context
     *
     * @return array
     */
    public function buildUnits(
        array $Units,
        Template $Tpl,
        array $block,
        array $config,
        array $context
    ): array {
        $currentUnitGroup = '';
        $last = array_key_last($Units);
        return array_map(
            function (
                Unit $Unit,
                int $index
            ) use (
                $Tpl,
                $block,
                $config,
                $context,
                &$currentUnitGroup,
                $last
            ) {
                if (config('unit_group') === 'on') {
                    $unitGroup = [
                        'open' => false,
                        'close' => false,
                        'last' => false,
                    ];
                    if (!empty($Unit->getGroup())) {
                        $unitGroup = [
                            ...$unitGroup,
                            ...(!$Unit->isClearGroup() ? [
                                'open' => true,
                            ] : []),
                            'close' => !empty($currentUnitGroup)
                        ];
                        $currentUnitGroup = $Unit->isClearGroup() ? null : $Unit->getGroup();
                    }
                    if ($index === $last && !empty($currentUnitGroup)) {
                        $unitGroup = [
                            ...$unitGroup,
                            'last' => true
                        ];
                    }
                }

                return [
                    ...(config('unit_group') === 'on' ? [
                        'unitGroup' => $unitGroup
                    ] : []),
                    ...$this->buildUnit(
                        $Unit,
                        $Tpl,
                        $block,
                        $config,
                        $context
                    )
                ];
            },
            $Units,
            array_keys($Units)
        );
    }

    /**
     * メイン画像の組み立て
     *
     * @param Unit $Unit
     * @param Template $Tpl
     * @param array $block
     * @param array $config
     * @param array{
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
     * } $context
     *
     * @return array
     */
    public function buildPrimaryImage(
        Unit $PrimaryImage,
        Template $Tpl,
        array $block,
        array $config,
        array $context
    ): array {
        if (
            0 ||
            $PrimaryImage->isHidden() ||
            ($PrimaryImage->getType() === UnitType::IMAGE && empty($PrimaryImage->getField2())) ||
            ($PrimaryImage->getType() === UnitType::MEDIA && empty($PrimaryImage->getField1()))
        ) {
            return [
                'x' => $config['imageX'],
                'y' => $config['imageY']
            ];
        }

        $vars = $this->buildUnit(
            $PrimaryImage,
            $Tpl,
            $block,
            $config,
            $context
        );

        $unitX = $PrimaryImage->getType() === UnitType::MEDIA ? $vars['media']['x'] : $vars['x'];
        $unitY = $PrimaryImage->getType() === UnitType::MEDIA ? $vars['media']['y'] : $vars['y'];

        if ($config['imageTrim'] === 'on') {
            if ($unitX > $config['imageX'] && $unitY > $config['imageY']) {
                if (($unitX / $config['imageX']) < ($unitY / $config['imageY'])) {
                    $x = $config['imageX'];
                    if ($config['imageX'] > 0 && ($unitX / $config['imageX']) > 0) {
                        $y = round($unitY / ($unitX / $config['imageX']));
                    } else {
                        $y = 0;
                    }
                } else {
                    $y = $config['imageY'];
                    if ($config['imageY'] > 0 && ($unitY / $config['imageY']) > 0) {
                        $x = round($unitX / ($unitY / $config['imageY']));
                    } else {
                        $x = 0;
                    }
                }
            } else {
                if ($unitX < $config['imageX']) {
                    $x = $config['imageX'];
                    if ($config['imageX'] > 0 && $unitX > 0) {
                        $y = round($unitY * ($config['imageX'] / $unitX));
                    } else {
                        $y = 0;
                    }
                } else if ($unitY < $config['imageY']) {
                    $y = $config['imageY'];
                    if ($config['imageY'] > 0 && $unitY > 0) {
                        $x = round($unitX * ($config['imageY'] / $unitY));
                    } else {
                        $x = 0;
                    }
                } else {
                    if (($config['imageX'] - $unitX) > ($config['imageY'] - $unitY)) {
                        $x = $config['imageX'];
                        if ($config['imageX'] > 0 && $unitX > 0) {
                            $y = round($unitY * ($config['imageX'] / $unitX));
                        } else {
                            $y = 0;
                        }
                    } else {
                        $y   = $config['imageY'];
                        if ($config['imageY'] > 0 && $unitY > 0) {
                            $x = round($unitX * ($config['imageY'] / $unitY));
                        } else {
                            $x = 0;
                        }
                    }
                }
            }
        } else {
            if ($unitX > $config['imageX']) {
                if ($unitY > $config['imageY']) {
                    if (($unitX - $config['imageX']) < ($unitY - $config['imageY'])) {
                        $y   = $config['imageY'];
                        if ($config['imageY'] > 0 && ($unitY / $config['imageY']) > 0) {
                            $x = round($unitX / ($unitY / $config['imageY']));
                        } else {
                            $x = 0;
                        }
                    } else {
                        $x = $config['imageX'];
                        if ($config['imageX'] > 0 && ($unitX / $config['imageX']) > 0) {
                            $y = round($unitY / ($unitX / $config['imageX']));
                        } else {
                            $y = 0;
                        }
                    }
                } else {
                    $x = $config['imageX'];
                    $y = round($unitY / ($unitX / $config['imageX']));
                }
            } else if ($unitY > $config['imageY']) {
                $y = $config['imageY'];
                $x = round($unitX / ($unitY / $config['imageY']));
            } else {
                if ($config['imageZoom'] === 'on') {
                    if (($config['imageX'] - $unitX) > ($config['imageY'] - $unitY)) {
                        $y = $config['imageY'];
                        $x = round($unitX * ($config['imageY'] / $unitY));
                    } else {
                        $x = $config['imageX'];
                        $y = round($unitY * ($config['imageX'] / $unitX));
                    }
                } else {
                    $x = $unitX;
                    $y = $unitY;
                }
            }
        }
        //-------
        // align
        if ($config['imageTrim'] !== 'on' && $config['imageCenter'] === 'on') {
            if ($x > $config['imageX']) {
                $left = round((-1 * ($x - $config['imageX'])) / 2);
            } else {
                $left = round(($config['imageX'] - $x) / 2);
            }
            if ($y > $config['imageY']) {
                $top = round((-1 * ($y - $config['imageY'])) / 2);
            } else {
                $top = round(($config['imageY'] - $y) / 2);
            }
        } else {
            $left   = 0;
            $top    = 0;
        }
        return [
            ...$vars,
            'x' => $x,
            'y' => $y,
            'top' => $top,
            'left' => $left
        ];
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
    public function buildPagenation(
        int $page,
        int $limit,
        int $amount,
        int $delta,
        string $curAttr,
        array $Q = []
    ): array {
        if (!ADMIN) {
            $Q['query'] = [];
        }
        if (KEYWORD) {
            $Q['keyword'] = KEYWORD;
        }

        $from = ($page - 1) * $limit;
        $to = ($amount < $from + $limit) ? $amount : $from + $limit;

        $vars = [
            'itemsAmount' => $amount,
            'itemsFrom' => $from + 1,
            'itemsTo' => $to,
            'backLink' => [],
            'firstPageUrl' => null,
            'firstPage' => null,
            'page' => [],
            'forwardLink' => [],
            'lastPageUrl' => null,
            'lastPage' => null
        ];

        $lastPage = intval(ceil($amount / $limit));
        $fromPage = 1 > ($page - $delta) ? 1 : ($page - $delta);
        $toPage = $lastPage < ($page + $delta) ? $lastPage : ($page + $delta);

        if ($lastPage > 1) {
            $vars['page'] = array_map(
                function (int $page) use ($curAttr, $Q) {
                    return [
                        'url' => acmsLink([
                            ...$Q,
                            'page' => $page
                        ]),
                        ...($page === intval(PAGE) ? [
                            'pageCurAttr' => $curAttr
                        ] : [])
                    ];
                },
                range($fromPage, $toPage)
            );
        }

        if ($toPage !== $lastPage) {
            $vars = [
                ...$vars,
                'lastPageUrl' => acmsLink([
                    ...$Q,
                    'page' => $lastPage,
                ]),
                'lastPage' => $lastPage,
            ];
        }

        if (1 < $fromPage) {
            $vars = [
                ...$vars,
                'firstPageUrl' => acmsLink([
                    ...$Q,
                    'page' => 1,
                ]),
                'firstPage' => 1,
            ];
        }

        if (1 < $page) {
            $vars = [
                ...$vars,
                'backLink' => [
                    'url' => acmsLink([
                        ...$Q,
                        'page' => ($page > 2) ? $page - 1 : false
                    ]),
                    'backNum' => $limit,
                    'backPage' => ($page > 1) ? $page - 1 : false
                ]
            ];
        }

        if ($page !== $lastPage) {
            $vars = [
                ...$vars,
                'forwardLink' => [
                    'url' => acmsLink([
                        ...$Q,
                        'page' => $page + 1
                    ]),
                    'forwardNum' => $limit < ($amount - ($from + $limit)) ? $limit : $amount - ($from + $limit),
                    'forwardPage' => $page + 1
                ]
            ];
        }

        return [
            ...$vars,
            ...($page - $delta > 2 ? [
                'omitBeforePage' => []
            ] : []),
            ...($lastPage - $page - $delta > 1 ? [
                'omitAfterPage' => []
            ] : [])
        ];
    }

    /**
     * 前後エントリーのテンプレート組み立て
     *
     * @param Entry|null $PrevEntry
     * @param Entry|null $NextEntry
     * @param Template $Tpl
     * @param array $config
     * @param array{
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
     * } $context
     *
     * @return array
     */
    public function buildSerialEntry(
        ?Entry $PrevEntry,
        ?Entry $NextEntry,
        Template $Tpl,
        array $config,
        array $context
    ): array {
        $config = [
            ...$config,
            'relatedEntryOn' => 'off',
            'tagOn' => 'off',
            'subCategory' => 'off',
            'fullTextOn' => 'off',
            'unitInfoOn' => 'off'
        ];
        return [
            ...(!is_null($PrevEntry) ? [
                'prevEntry' => $this->buildEntry(
                    $PrevEntry,
                    $Tpl,
                    ['prevEntry'],
                    $config,
                    $context
                )
            ] : [
                'prevNotFound' => new \stdClass()
            ]),
            ...(!is_null($NextEntry) ? [
                'nextEntry' => $this->buildEntry(
                    $NextEntry,
                    $Tpl,
                    ['nextEntry'],
                    $config,
                    $context
                )
            ] : [
                'nextNotFound' => new \stdClass()
            ]),
        ];
    }

    public function buildMedia(Media $Media, \Template $Tpl, array $block): array
    {
        return [
            'id' => $Media->getId(),
            'status' => $Media->getStatus(),
            'path' => $Media->getPath(),
            'thumbnail' => $Media->getThumbnail(),
            'fileName' => $Media->getFileName(),
            'imageSize' => $Media->getImageSize(),
            'fileSize' => $Media->getFileSize(),
            'type' => $Media->getType(),
            'extension' => $Media->getExtension(),
            'original' => $Media->getOriginal(),
            ...$this->buildDate(
                $Media->getUploadDate()->format('Y-m-d H:i:s'),
                $Tpl,
                $block,
                'date#'
            ),
            ...$this->buildDate(
                $Media->getUpdateDate()->format('Y-m-d H:i:s'),
                $Tpl,
                $block,
                'udate#'
            ),
            'caption' => $Media->getCaption(),
            'link' => $Media->getLink(),
            'alt' => $Media->getAlt(),
            'text' => $Media->getText(),
            'page' => $Media->getPage(),
            'focalX' => $Media->getFocalX(),
            'focalY' => $Media->getFocalY(),
            'width' => $Media->getWidth(),
            'height' => $Media->getHeight(),
            'ratio' => $Media->getRatio()
        ];
    }
}
