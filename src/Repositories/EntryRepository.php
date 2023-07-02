<?php

namespace Acms\Plugins\V2\Repositories;

use DateTimeImmutable;
use ACMS_Filter;
use ACMS_RAM;
use SQL;
use SQL_Select;
use Acms\Services\Facades\Database as DB;
use Acms\Plugins\V2\Entities\Master\UnitType;
use Acms\Plugins\V2\Entities\Blog;
use Acms\Plugins\V2\Entities\Category;
use Acms\Plugins\V2\Entities\Entry;
use Acms\Plugins\V2\Entities\Geo;
use Acms\Plugins\V2\Entities\Media;
use Acms\Plugins\V2\Entities\Module;
use Acms\Plugins\V2\Entities\RelatedEntryGroup;
use Acms\Plugins\V2\Entities\Tag;
use Acms\Plugins\V2\Entities\Unit;
use Acms\Plugins\V2\Entities\User;

use function Symfony\Component\String\u;

/**
 * エントリーのリポジトリ
 */
class EntryRepository
{
    /**
     * @var MediaRepository
     */
    protected MediaRepository $MediaRepository;

    /**
     * @var ModuleRepository
     */
    protected ModuleRepository $ModuleRepository;

    /**
     * 初期化処理
     *
     * @param \Acms\Services\Container
     */
    public function __construct(\Acms\Services\Container $container)
    {
        $this->MediaRepository = $container->make('v2.repositry.media');
        $this->ModuleRepository = $container->make('v2.repositry.module');
    }

    /**
     * エントリーを取得する
     *
     * @param array $context
     * @param array $axis
     * @param array $config
     *
     * @return array
     */
    public function find(array $context, array $axis, array $config): array
    {
        [$indexQuery, $amountQuery] = $this->buildQuery($context, $axis, $config);
        $rows = DB::query($indexQuery, 'all');
        foreach ($rows as $entry) {
            ACMS_RAM::entry($entry['entry_id'], $entry);
        }
        $eagerLoad = $this->eagerLoad($rows, $config);

        return [
            array_map(
                function (array $entry) use ($eagerLoad) {
                    return $this->createEntry(
                        $entry,
                        $eagerLoad['entryField'],
                        $eagerLoad['userField'],
                        $eagerLoad['categoryField'],
                        $eagerLoad['blogField'],
                        $eagerLoad['media'],
                        $eagerLoad['tag'],
                        $eagerLoad['subCategory'],
                        $eagerLoad['relatedEntry'],
                        $eagerLoad['module'],
                        $eagerLoad['unit']
                    );
                },
                $rows
            ),
            $amountQuery
        ];
    }

    /**
     * 前後エントリーデータの取得
     *
     * @param Entry $Entry
     * @param array $context
     * @param array $axis
     * @param array $config
     *
     * @return Entry[]
     */
    public function findSerialEntries(
        Entry $Entry,
        array $context,
        array $axis,
        array $config
    ): array {
        [$prevSql, $nextSql] = $this->buildSerialEntryQuery(
            $Entry,
            $context,
            $axis,
            $config
        );
        $prev = DB::query($prevSql, 'row');
        $next = DB::query($nextSql, 'row');

        $eids = [];
        $uids = [];
        $cids = [];
        $bids = [];
        $mids = [];
        foreach ([$prev, $next] as $entry) {
            if (!empty($entry)) {
                if (array_key_exists('entry_id', $entry)) {
                    $eids[] = intval($entry['entry_id']);
                }
                if (array_key_exists('entry_user_id', $entry)) {
                    $uids[] = intval($entry['entry_user_id']);
                }
                if (array_key_exists('entry_last_update_user_id', $entry)) {
                    $uids[] = intval($entry['entry_last_update_user_id']);
                }
                if (array_key_exists('entry_category_id', $entry)) {
                    $cids[] = intval($entry['entry_category_id']);
                }
                if (array_key_exists('entry_blog_id', $entry)) {
                    $bids[] = intval($entry['entry_blog_id']);
                }
                if (array_key_exists('last_update_user_blog_blog_id', $entry)) {
                    $bids[] = intval($entry['last_update_user_blog_blog_id']);
                }
                if (array_key_exists('category_blog_id', $entry)) {
                    $bids[] = intval($entry['category_blog_id']);
                }
                if (
                    1 &&
                    !is_null($entry['entry_primary_image']) &&
                    $entry['primary_image_unit_type'] === UnitType::MEDIA
                ) {
                    $mids[] = intval($entry['primary_image_unit_field_1']);
                }
            }
        }

        $entryField = eagerLoadField(array_unique($eids), 'eid');
        $userField = eagerLoadField(array_unique($uids), 'uid');
        $categoryField = eagerLoadField(array_unique($cids), 'cid');
        $blogField = eagerLoadField(array_unique($bids), 'bid');
        $medias = array_reduce(
            $this->MediaRepository->findByIds(array_unique($mids)),
            function (array $medias, Media $Media) {
                return [
                    ...$medias,
                    $Media->getId() => $Media
                ];
            },
            []
        );

        return [
            !empty($prev) ? $this->createEntry(
                $prev,
                $entryField,
                $userField,
                $categoryField,
                $blogField,
                $medias
            ) : null,
            !empty($next) ? $this->createEntry(
                $next,
                $entryField,
                $userField,
                $categoryField,
                $blogField,
                $medias
            ) : null
        ];
    }

    /**
     * sqlの組み立て
     *
     * @param array $config
     *
     * @return SQL_Select
     */
    protected function buildBaseSql(array $config): SQL_Select
    {
        $sql = SQL::newSelect('entry', 'entry');
        $sql->addLeftJoin('category', 'category_id', 'entry_category_id', 'category', 'entry');
        $sql->addLeftJoin('blog', 'blog_id', 'entry_blog_id', 'blog', 'entry');
        $sql->addLeftJoin('blog', 'blog_id', 'category_blog_id', 'category_blog', 'category');
        $sql->addLeftJoin('user', 'user_id', 'entry_user_id', 'user', 'entry');
        $sql->addLeftJoin('user', 'user_id', 'entry_last_update_user_id', 'last_update_user', 'entry');
        $sql->addLeftJoin('blog', 'blog_id', 'user_blog_id', 'user_blog', 'user');
        $sql->addLeftJoin('blog', 'blog_id', 'user_blog_id', 'last_update_user_blog', 'last_update_user');
        $sql->addLeftJoin('column', 'column_id', 'entry_primary_image', 'primary_image', 'entry');
        $sql->addLeftJoin('geo', 'geo_eid', 'entry_id', 'geo', 'entry');
        if (isset($config['subCategory']) && $config['subCategory'] === 'on') {
            $sql->addLeftJoin('entry_sub_category', 'entry_sub_category_eid', 'entry_id', 'sub_category', 'entry');
        }
        $this->setSelect($sql);

        return $sql;
    }

    /**
     * sqlの組み立て
     *
     * @param array $context
     * @param array $axis
     * @param array $config
     *
     * @return string
     */
    protected function buildQuery(array $context, array $axis, array $config)
    {
        $sql1 = $this->buildBaseSql($config);
        $this->filterQuery(
            $sql1,
            $context,
            $axis,
            $config
        );

        $q1 = $sql1->get(dsn());

        if (isset($config['subCategory']) && $config['subCategory'] === 'on') {
            $sql2 = $this->buildBaseSql($config);
            $this->filterQuery(
                $sql2,
                $context,
                $axis,
                $config,
                'sub_category.entry_sub_category_id'
            );


            $q2 = $sql2->get(dsn());

            $sql = '((' . $q1 . ') UNION (' . $q2 . '))';
        } else {
            $sql = '(' . $q1 . ')';
        }
        $indexSql = SQL::newSelect($sql, 'master');
        $amountSql = $this->getAmountSql($indexSql); // limitする前のクエリから全件取得のクエリを準備しておく
        $this->orderQuery($indexSql, $context, $config);
        $this->limitQuery($indexSql, $config['limit'], $config['offset'], $context['page']);

        $indexSql->setSelect(' *');
        $indexSql->addSelect('geo_geometry', 'latitude', null, POINT_Y);
        $indexSql->addSelect('geo_geometry', 'longitude', null, POINT_X);

        return [$indexSql->get(dsn(['prefix' => ''])), $amountSql->get(dsn(['prefix' => '']))];
    }

    /**
     * 前後エントリーsqlの組み立て
     *
     * @param Entry $Entry
     * @param array $context
     * @param array $axis
     * @param array $config
     *
     * @return string[]
     */
    protected function buildSerialEntryQuery(
        Entry $Entry,
        array $context,
        array $axis,
        array $config
    ): array {
        $sql = $this->buildBaseSql($config);
        ACMS_Filter::entrySpan($sql, $context['start'], $context['end']);
        ACMS_Filter::entrySession(
            $sql,
            null,
            isset($config['hiddenPrivateEntry']) && $config['hiddenPrivateEntry'] === 'on'
        );
        $sql->addWhereOpr('entry_link', ''); // リンク先URLが設定されているエントリーはリンクに含まないようにする

        $this->userFilterQuery($sql, $context['uid']);
        if ($config['serialEntryIgnoreCategory'] !== 'on') {
            $this->categoryFilterQuery(
                $sql,
                $context['cid'],
                $axis['cid']
            );
        }
        $this->blogFilterQuery($sql, $context['bid'], $axis['bid'], $config, false);

        if (!empty($context['tags'])) {
            $this->tagFilterQuery($sql, $context['tags']);
        }

        if (!empty($context['keyword'])) {
            $this->keywordFilterQuery($sql, $context['keyword']);
        }

        if (!$context['field']->isNull()) {
            $this->fieldFilterQuery($sql, $context['field']);
        }

        $this->otherFilterQuery($sql, $config);
        $sql->setLimit(1);

        $orders = array_map(
            function (string $order) {
                return explode('-', $order);
            },
            $config['order']
        );
        $sort1 = isset($orders[0][0]) ? $orders[0][0] : null;
        $ascOrDesc1 = isset($orders[0][1]) ? $orders[0][1] : 'desc';
        $sort2 = isset($orders[1][0]) ? $orders[1][0] : null;
        $ascOrDesc2 = isset($orders[1][1]) ? $orders[1][1] : 'desc';

        switch ($sort1) {
            case 'id':
            case 'code':
            case 'status':
            case 'sort':
            case 'user_sort':
            case 'category_sort':
            case 'title':
            case 'link':
            case 'summary_range':
            case 'indexing':
            case 'primary_image':
                $field = 'entry_' . $sort1;
                $value = $Entry->{'get' . u($sort1)->camel()->title()}();
                break;
            case 'datetime':
            case 'start_datetime':
            case 'end_datetime':
            case 'posted_datetime':
                $field = 'entry_' . $sort1;
                $value = $Entry->{'get' . u($sort1)->camel()->title()}()->format('Y-m-d H:i:s');
                break;
            case 'category_id':
                $field = 'entry_' . $sort1;
                $value = $Entry->getCategory()->getId();
                break;
            case 'user_id':
                $field = 'entry_' . $sort1;
                $value = $Entry->getUser()->getId();
                break;
            case 'blog_id':
                $field = 'entry_' . $sort1;
                $value = $Entry->getBlog()->getId();
                break;
            default:
                $field = 'entry_id';
                $value = $Entry->getId();
                break;
        }

        // prev entry
        $prevSql = clone $sql;
        $prevWhere1  = SQL::newWhere();
        $prevWhere1->addWhereOpr($field, $value, '=');
        $prevWhere1->addWhereOpr('entry_id', $Entry->getId(), '<');
        $prevWhere2 = SQL::newWhere();
        $prevWhere2->addWhere($prevWhere1);
        $prevWhere2->addWhereOpr(
            $field,
            $value,
            $ascOrDesc1 === 'desc' ? '<' : '>',
            'OR'
        );
        $prevSql->addWhere($prevWhere2);
        $sortFd = ACMS_Filter::entryOrder(
            $prevSql,
            [
                $sort1 . '-' . $ascOrDesc1,
                $sort2 . '-' . $ascOrDesc2
            ],
            $config['noNarrowDownSort'] !== 'on' ? $context['uid'] : null,
            $config['noNarrowDownSort'] !== 'on' ? $context['cid'] : null,
            false,
            $config['orderFieldName']
        );
        if ($sortFd) {
            $prevSql->setGroup($sortFd);
        }
        $prevSql->addGroup('entry_id');

        // next entry
        $nextSql = clone $sql;
        $nextWhere1  = SQL::newWhere();
        $nextWhere1->addWhereOpr($field, $value, '=');
        $nextWhere1->addWhereOpr('entry_id', $Entry->getId(), '>');
        $nextWhere2 = SQL::newWhere();
        $nextWhere2->addWhere($nextWhere1);
        $nextWhere2->addWhereOpr(
            $field,
            $value,
            $ascOrDesc1 === 'desc' ? '>' : '<',
            'OR'
        );
        $nextSql->addWhere($nextWhere2);
        $sortFd = ACMS_Filter::entryOrder(
            $nextSql,
            [
                $sort1 . '-' . $ascOrDesc1 === 'desc' ? 'asc' : 'desc',
                $sort2 . '-' . $ascOrDesc2 === 'desc' ? 'asc' : 'desc'
            ],
            $config['noNarrowDownSort'] !== 'on' ? $context['uid'] : null,
            $config['noNarrowDownSort'] !== 'on' ? $context['cid'] : null,
            false,
            $config['orderFieldName']
        );
        if ($sortFd) {
            $nextSql->setGroup($sortFd);
        }
        $nextSql->addGroup('entry_id');

        return [$prevSql->get(dsn()), $nextSql->get(dsn())];
    }

    protected function setSelect(SQL_Select $sql)
    {
        foreach (
            [
            [
                'field' => 'entry_id',
                'alias' => null,
                'scope' => null,
                'function' => null
            ],
            [
                'field' => 'entry_code',
                'alias' => null,
                'scope' => null,
                'function' => null
            ],
            [
                'field' => 'entry_status',
                'alias' => null,
                'scope' => null,
                'function' => null
            ],
            [
                'field' => 'entry_approval',
                'alias' => null,
                'scope' => null,
                'function' => null
            ],
            [
                'field' => 'entry_form_status',
                'alias' => null,
                'scope' => null,
                'function' => null
            ],
            [
                'field' => 'entry_sort',
                'alias' => null,
                'scope' => null,
                'function' => null
            ],
            [
                'field' => 'entry_user_sort',
                'alias' => null,
                'scope' => null,
                'function' => null
            ],
            [
                'field' => 'entry_category_sort',
                'alias' => null,
                'scope' => null,
                'function' => null
            ],
            [
                'field' => 'entry_title',
                'alias' => null,
                'scope' => null,
                'function' => null
            ],
            [
                'field' => 'entry_link',
                'alias' => null,
                'scope' => null,
                'function' => null
            ],
            [
                'field' => 'entry_hash',
                'alias' => null,
                'scope' => null,
                'function' => null
            ],
            [
                'field' => 'entry_datetime',
                'alias' => null,
                'scope' => null,
                'function' => null
            ],
            [
                'field' => 'entry_start_datetime',
                'alias' => null,
                'scope' => null,
                'function' => null
            ],
            [
                'field' => 'entry_end_datetime',
                'alias' => null,
                'scope' => null,
                'function' => null
            ],
            [
                'field' => 'entry_posted_datetime',
                'alias' => null,
                'scope' => null,
                'function' => null
            ],
            [
                'field' => 'entry_updated_datetime',
                'alias' => null,
                'scope' => null,
                'function' => null
            ],
            [
                'field' => 'entry_summary_range',
                'alias' => null,
                'scope' => null,
                'function' => null
            ],
            [
                'field' => 'entry_indexing',
                'alias' => null,
                'scope' => null,
                'function' => null
            ],
            [
                'field' => 'entry_primary_image',
                'alias' => null,
                'scope' => null,
                'function' => null
            ],
            [
                'field' => 'entry_current_rev_id',
                'alias' => null,
                'scope' => null,
                'function' => null
            ],
            [
                'field' => 'entry_last_update_user_id',
                'alias' => null,
                'scope' => null,
                'function' => null
            ],
            [
                'field' => 'entry_category_id',
                'alias' => null,
                'scope' => null,
                'function' => null
            ],
            [
                'field' => 'entry_user_id',
                'alias' => null,
                'scope' => null,
                'function' => null
            ],
            [
                'field' => 'entry_form_id',
                'alias' => null,
                'scope' => null,
                'function' => null
            ],
            [
                'field' => 'entry_blog_id',
                'alias' => null,
                'scope' => null,
                'function' => null
            ],
            [
                'field' => 'blog_id',
                'alias' => 'blog_id',
                'scope' => 'blog',
                'function' => null
            ],
            [
                'field' => 'blog_code',
                'alias' => 'blog_code',
                'scope' => 'blog',
                'function' => null
            ],
            [
                'field' => 'blog_status',
                'alias' => 'blog_status',
                'scope' => 'blog',
                'function' => null
            ],
            [
                'field' => 'blog_parent',
                'alias' => 'blog_parent',
                'scope' => 'blog',
                'function' => null
            ],
            [
                'field' => 'blog_name',
                'alias' => 'blog_name',
                'scope' => 'blog',
                'function' => null
            ],
            [
                'field' => 'blog_left',
                'alias' => 'blog_left',
                'scope' => 'blog',
                'function' => null
            ],
            [
                'field' => 'blog_right',
                'alias' => 'blog_right',
                'scope' => 'blog',
                'function' => null
            ],
            [
                'field' => 'blog_domain',
                'alias' => 'blog_domain',
                'scope' => 'blog',
                'function' => null
            ],
            [
                'field' => 'blog_indexing',
                'alias' => 'blog_indexing',
                'scope' => 'blog',
                'function' => null
            ],
            [
                'field' => 'blog_sort',
                'alias' => 'blog_sort',
                'scope' => 'blog',
                'function' => null
            ],
            [
                'field' => 'blog_generated_datetime',
                'alias' => 'blog_generated_datetime',
                'scope' => 'blog',
                'function' => null
            ],
            [
                'field' => 'category_id',
                'alias' => 'category_id',
                'scope' => 'category',
                'function' => null
            ],
            [
                'field' => 'category_code',
                'alias' => 'category_code',
                'scope' => 'category',
                'function' => null
            ],
            [
                'field' => 'category_status',
                'alias' => 'category_status',
                'scope' => 'category',
                'function' => null
            ],
            [
                'field' => 'category_parent',
                'alias' => 'category_parent',
                'scope' => 'category',
                'function' => null
            ],
            [
                'field' => 'category_sort',
                'alias' => 'category_sort',
                'scope' => 'category',
                'function' => null
            ],
            [
                'field' => 'category_name',
                'alias' => 'category_name',
                'scope' => 'category',
                'function' => null
            ],
            [
                'field' => 'category_scope',
                'alias' => 'category_scope',
                'scope' => 'category',
                'function' => null
            ],
            [
                'field' => 'category_indexing',
                'alias' => 'category_indexing',
                'scope' => 'category',
                'function' => null
            ],
            [
                'field' => 'category_left',
                'alias' => 'category_left',
                'scope' => 'category',
                'function' => null
            ],
            [
                'field' => 'category_right',
                'alias' => 'category_right',
                'scope' => 'category',
                'function' => null
            ],
            [
                'field' => 'category_blog_id',
                'alias' => 'category_blog_id',
                'scope' => 'category',
                'function' => null
            ],
            [
                'field' => 'user_id',
                'alias' => 'user_id',
                'scope' => 'user',
                'function' => null
            ],
            [
                'field' => 'user_code',
                'alias' => 'user_code',
                'scope' => 'user',
                'function' => null
            ],
            [
                'field' => 'user_status',
                'alias' => 'user_status',
                'scope' => 'user',
                'function' => null
            ],
            [
                'field' => 'user_sort',
                'alias' => 'user_sort',
                'scope' => 'user',
                'function' => null
            ],
            [
                'field' => 'user_name',
                'alias' => 'user_name',
                'scope' => 'user',
                'function' => null
            ],
            [
                'field' => 'user_mail',
                'alias' => 'user_mail',
                'scope' => 'user',
                'function' => null
            ],
            [
                'field' => 'user_url',
                'alias' => 'user_url',
                'scope' => 'user',
                'function' => null
            ],
            [
                'field' => 'user_icon',
                'alias' => 'user_icon',
                'scope' => 'user',
                'function' => null
            ],
            [
                'field' => 'user_auth',
                'alias' => 'user_auth',
                'scope' => 'user',
                'function' => null
            ],
            [
                'field' => 'user_generated_datetime',
                'alias' => 'user_generated_datetime',
                'scope' => 'user',
                'function' => null
            ],
            [
                'field' => 'user_updated_datetime',
                'alias' => 'user_updated_datetime',
                'scope' => 'user',
                'function' => null
            ],
            [
                'field' => 'user_indexing',
                'alias' => 'user_indexing',
                'scope' => 'user',
                'function' => null
            ],
            [
                'field' => 'user_blog_id',
                'alias' => 'user_blog_id',
                'scope' => 'user',
                'function' => null
            ],
            [
                'field' => 'user_id',
                'alias' => 'last_update_user_user_id',
                'scope' => 'last_update_user',
                'function' => null
            ],
            [
                'field' => 'user_code',
                'alias' => 'last_update_user_user_code',
                'scope' => 'last_update_user',
                'function' => null
            ],
            [
                'field' => 'user_status',
                'alias' => 'last_update_user_user_status',
                'scope' => 'last_update_user',
                'function' => null
            ],
            [
                'field' => 'user_sort',
                'alias' => 'last_update_user_user_sort',
                'scope' => 'last_update_user',
                'function' => null
            ],
            [
                'field' => 'user_name',
                'alias' => 'last_update_user_user_name',
                'scope' => 'last_update_user',
                'function' => null
            ],
            [
                'field' => 'user_mail',
                'alias' => 'last_update_user_user_mail',
                'scope' => 'last_update_user',
                'function' => null
            ],
            [
                'field' => 'user_url',
                'alias' => 'last_update_user_user_url',
                'scope' => 'last_update_user',
                'function' => null
            ],
            [
                'field' => 'user_icon',
                'alias' => 'last_update_user_user_icon',
                'scope' => 'last_update_user',
                'function' => null
            ],
            [
                'field' => 'user_auth',
                'alias' => 'last_update_user_user_auth',
                'scope' => 'last_update_user',
                'function' => null
            ],
            [
                'field' => 'user_generated_datetime',
                'alias' => 'last_update_user_user_generated_datetime',
                'scope' => 'last_update_user',
                'function' => null
            ],
            [
                'field' => 'user_updated_datetime',
                'alias' => 'last_update_user_user_updated_datetime',
                'scope' => 'last_update_user',
                'function' => null
            ],
            [
                'field' => 'user_indexing',
                'alias' => 'last_update_user_user_indexing',
                'scope' => 'last_update_user',
                'function' => null
            ],
            [
                'field' => 'user_blog_id',
                'alias' => 'last_update_user_user_blog_id',
                'scope' => 'last_update_user',
                'function' => null
            ],
            [
                'field' => 'blog_id',
                'alias' => 'user_blog_blog_id',
                'scope' => 'user_blog',
                'function' => null
            ],
            [
                'field' => 'blog_code',
                'alias' => 'user_blog_blog_code',
                'scope' => 'user_blog',
                'function' => null
            ],
            [
                'field' => 'blog_status',
                'alias' => 'user_blog_blog_status',
                'scope' => 'user_blog',
                'function' => null
            ],
            [
                'field' => 'blog_parent',
                'alias' => 'user_blog_blog_parent',
                'scope' => 'user_blog',
                'function' => null
            ],
            [
                'field' => 'blog_sort',
                'alias' => 'user_blog_blog_sort',
                'scope' => 'user_blog',
                'function' => null
            ],
            [
                'field' => 'blog_left',
                'alias' => 'user_blog_blog_left',
                'scope' => 'user_blog',
                'function' => null
            ],
            [
                'field' => 'blog_right',
                'alias' => 'user_blog_blog_right',
                'scope' => 'user_blog',
                'function' => null
            ],
            [
                'field' => 'blog_name',
                'alias' => 'user_blog_blog_name',
                'scope' => 'user_blog',
                'function' => null
            ],
            [
                'field' => 'blog_domain',
                'alias' => 'user_blog_blog_domain',
                'scope' => 'user_blog',
                'function' => null
            ],
            [
                'field' => 'blog_indexing',
                'alias' => 'user_blog_blog_indexing',
                'scope' => 'user_blog',
                'function' => null
            ],
            [
                'field' => 'blog_generated_datetime',
                'alias' => 'user_blog_blog_generated_datetime',
                'scope' => 'user_blog',
                'function' => null
            ],
            [
                'field' => 'blog_id',
                'alias' => 'last_update_user_blog_blog_id',
                'scope' => 'last_update_user_blog',
                'function' => null
            ],
            [
                'field' => 'blog_code',
                'alias' => 'last_update_user_blog_blog_code',
                'scope' => 'last_update_user_blog',
                'function' => null
            ],
            [
                'field' => 'blog_status',
                'alias' => 'last_update_user_blog_blog_status',
                'scope' => 'last_update_user_blog',
                'function' => null
            ],
            [
                'field' => 'blog_parent',
                'alias' => 'last_update_user_blog_blog_parent',
                'scope' => 'last_update_user_blog',
                'function' => null
            ],
            [
                'field' => 'blog_sort',
                'alias' => 'last_update_user_blog_blog_sort',
                'scope' => 'last_update_user_blog',
                'function' => null
            ],
            [
                'field' => 'blog_left',
                'alias' => 'last_update_user_blog_blog_left',
                'scope' => 'last_update_user_blog',
                'function' => null
            ],
            [
                'field' => 'blog_right',
                'alias' => 'last_update_user_blog_blog_right',
                'scope' => 'last_update_user_blog',
                'function' => null
            ],
            [
                'field' => 'blog_name',
                'alias' => 'last_update_user_blog_blog_name',
                'scope' => 'last_update_user_blog',
                'function' => null
            ],
            [
                'field' => 'blog_domain',
                'alias' => 'last_update_user_blog_blog_domain',
                'scope' => 'last_update_user_blog',
                'function' => null
            ],
            [
                'field' => 'blog_indexing',
                'alias' => 'last_update_user_blog_blog_indexing',
                'scope' => 'last_update_user_blog',
                'function' => null
            ],
            [
                'field' => 'blog_generated_datetime',
                'alias' => 'last_update_user_blog_blog_generated_datetime',
                'scope' => 'last_update_user_blog',
                'function' => null
            ],
            [
                'field' => 'blog_id',
                'alias' => 'category_blog_blog_id',
                'scope' => 'category_blog',
                'function' => null
            ],
            [
                'field' => 'blog_code',
                'alias' => 'category_blog_blog_code',
                'scope' => 'category_blog',
                'function' => null
            ],
            [
                'field' => 'blog_status',
                'alias' => 'category_blog_blog_status',
                'scope' => 'category_blog',
                'function' => null
            ],
            [
                'field' => 'blog_parent',
                'alias' => 'category_blog_blog_parent',
                'scope' => 'category_blog',
                'function' => null
            ],
            [
                'field' => 'blog_sort',
                'alias' => 'category_blog_blog_sort',
                'scope' => 'category_blog',
                'function' => null
            ],
            [
                'field' => 'blog_left',
                'alias' => 'category_blog_blog_left',
                'scope' => 'category_blog',
                'function' => null
            ],
            [
                'field' => 'blog_right',
                'alias' => 'category_blog_blog_right',
                'scope' => 'category_blog',
                'function' => null
            ],
            [
                'field' => 'blog_name',
                'alias' => 'category_blog_blog_name',
                'scope' => 'category_blog',
                'function' => null
            ],
            [
                'field' => 'blog_domain',
                'alias' => 'category_blog_blog_domain',
                'scope' => 'category_blog',
                'function' => null
            ],
            [
                'field' => 'blog_indexing',
                'alias' => 'category_blog_blog_indexing',
                'scope' => 'category_blog',
                'function' => null
            ],
            [
                'field' => 'blog_generated_datetime',
                'alias' => 'category_blog_blog_generated_datetime',
                'scope' => 'category_blog',
                'function' => null
            ],
            [
                'field' => 'column_id',
                'alias' => 'primary_image_unit_id',
                'scope' => 'primary_image',
                'function' => null
            ],
            [
                'field' => 'column_sort',
                'alias' => 'primary_image_unit_sort',
                'scope' => 'primary_image',
                'function' => null
            ],
            [
                'field' => 'column_align',
                'alias' => 'primary_image_unit_align',
                'scope' => 'primary_image',
                'function' => null
            ],
            [
                'field' => 'column_type',
                'alias' => 'primary_image_unit_type',
                'scope' => 'primary_image',
                'function' => null
            ],
            [
                'field' => 'column_attr',
                'alias' => 'primary_image_unit_attr',
                'scope' => 'primary_image',
                'function' => null
            ],
            [
                'field' => 'column_group',
                'alias' => 'primary_image_unit_group',
                'scope' => 'primary_image',
                'function' => null
            ],
            [
                'field' => 'column_size',
                'alias' => 'primary_image_unit_size',
                'scope' => 'primary_image',
                'function' => null
            ],
            [
                'field' => 'column_field_1',
                'alias' => 'primary_image_unit_field_1',
                'scope' => 'primary_image',
                'function' => null
            ],
            [
                'field' => 'column_field_2',
                'alias' => 'primary_image_unit_field_2',
                'scope' => 'primary_image',
                'function' => null
            ],
            [
                'field' => 'column_field_3',
                'alias' => 'primary_image_unit_field_3',
                'scope' => 'primary_image',
                'function' => null
            ],
            [
                'field' => 'column_field_4',
                'alias' => 'primary_image_unit_field_4',
                'scope' => 'primary_image',
                'function' => null
            ],
            [
                'field' => 'column_field_5',
                'alias' => 'primary_image_unit_field_5',
                'scope' => 'primary_image',
                'function' => null
            ],
            [
                'field' => 'column_field_6',
                'alias' => 'primary_image_unit_field_6',
                'scope' => 'primary_image',
                'function' => null
            ],
            [
                'field' => 'column_field_7',
                'alias' => 'primary_image_unit_field_7',
                'scope' => 'primary_image',
                'function' => null
            ],
            [
                'field' => 'column_field_8',
                'alias' => 'primary_image_unit_field_8',
                'scope' => 'primary_image',
                'function' => null
            ],
            [
                'field' => 'column_entry_id',
                'alias' => 'primary_image_unit_entry_id',
                'scope' => 'primary_image',
                'function' => null
            ],
            [
                'field' => 'column_blog_id',
                'alias' => 'primary_image_unit_blog_id',
                'scope' => 'primary_image',
                'function' => null
            ],
            [
                'field' => 'geo_geometry',
                'alias' => 'geo_geometry',
                'scope' => 'geo',
                'function' => null
            ],
            [
                'field' => 'geo_zoom',
                'alias' => 'geo_zoom',
                'scope' => 'geo',
                'function' => null
            ],
            ] as $select
        ) {
            $sql->addSelect(
                $select['field'],
                $select['alias'],
                $select['scope'],
                $select['function']
            );
        }
    }

    /**
     * orderクエリ組み立て
     *
     * @param SQL_Select $sql
     * @param array $context
     * @param array $config
     *
     * @return void
     */
    protected function orderQuery(SQL_Select $sql, array $context, array $config)
    {
        $sortFd = false;
        if (isset($config['noNarrowDownSort']) && $config['noNarrowDownSort'] === 'on') {
            // カテゴリー、ユーザー絞り込み時でも、絞り込み時用のソートを利用しない
            $sortFd = ACMS_Filter::entryOrder(
                $sql,
                $config['order'],
                null,
                null,
                false,
                $config['orderFieldName']
            );
        } else {
            $sortFd = ACMS_Filter::entryOrder(
                $sql,
                $config['order'],
                $context['uid'],
                $context['cid'],
                false,
                $config['orderFieldName']
            );
        }
        if ($sortFd) {
            $sql->setGroup($sortFd);
        }
        $sql->addGroup('entry_id');
    }

    /**
     * エントリー数取得sqlの準備
     *
     * @param SQL_Select $sql
     * @return SQL_Select
     */
    protected function getAmountSql(SQL_Select $sql)
    {
        $amountSql = new SQL_Select($sql);
        $amountSql->addSelect('DISTINCT(entry_id)', 'entry_amount', null, 'COUNT');

        return $amountSql;
    }

    /**
     * limitクエリ組み立て
     *
     * @param SQL_Select $sql
     * @param int $limit
     * @param int $offset
     * @param int|null $page
     *
     * @return void
     */
    protected function limitQuery(SQL_Select $sql, int $limit, int $offset, ?int $page)
    {
        $offset = ($page - 1) * $limit + $offset;
        $sql->setLimit($limit, $offset);
    }

    /**
     * 絞り込みクエリ組み立て
     *
     * @param SQL_Select $sql
     * @param array $context
     * @param array $axis
     * @param array $config
     *
     * @return void
     */
    protected function filterQuery(
        SQL_Select $sql,
        array $context,
        array $axis,
        array $config,
        string $filterCategoryFieldName = 'entry_category_id'
    ) {
        $private = isset($config['hiddenPrivateEntry']) && $config['hiddenPrivateEntry'] === 'on';
        ACMS_Filter::entrySpan($sql, $context['start'], $context['end']);
        ACMS_Filter::entrySession($sql, null, $private);

        // if (isset($config['relational']) && $config['relational'] === 'on') {
        //     return $this->relationalFilterQuery($sql, $context['eids']);
        // }

        $multi = false;
        $multi = $this->categoryFilterQuery(
            $sql,
            $context['cid'],
            $axis['cid'],
            $filterCategoryFieldName
        ) || $multi;
        $multi = $this->userFilterQuery($sql, $context['uid']) || $multi;
        $multi = $this->entryFilterQuery($sql, $context['eid']) || $multi;
        $this->blogFilterQuery($sql, $context['bid'], $axis['cid'], $config, $multi);

        if (!empty($context['tags'])) {
            $this->tagFilterQuery($sql, $context['tags']);
        }

        if (!empty($context['keyword'])) {
            $this->keywordFilterQuery($sql, $context['keyword']);
        }

        if (!$context['field']->isNull()) {
            $this->fieldFilterQuery($sql, $context['field']);
        }

        $this->otherFilterQuery($sql, $config);
    }

    /**
     * 関連エントリーの絞り込み
     *
     * @param SQL_Select $sql
     * @param array $eids
     *
     * @return void
     */
    protected function relationalFilterQuery(SQL_Select $sql, array $eids)
    {
        $sql->addWhereIn('entry_id', $eids);
    }

    /**
     * カテゴリーの絞り込み
     *
     * @param SQL_Select $sql
     * @param string|int|null $cid
     * @param string $axis
     * @param string $filterFieldName
     *
     * @return bool
     */
    protected function categoryFilterQuery(
        SQL_Select $sql,
        string|int|null $cid,
        string $axis,
        string $filterFieldName = 'entry_category_id'
    ): bool {
        $multi = false;
        if (!empty($cid)) {
            $categorySubQuery = SQL::newSelect('category');
            $categorySubQuery->setSelect('category_id');
            if (is_int($cid)) {
                if ($axis === 'self') {
                    $sql->addWhereOpr($filterFieldName, $cid);
                } else {
                    ACMS_Filter::categoryTree($categorySubQuery, $cid, $axis, 'category');
                }
            } elseif (strpos($cid, ',') !== false) {
                $categorySubQuery->addWhereIn('category_id', explode(',', $cid));
                $multi = true;
            }
            ACMS_Filter::categoryStatus($categorySubQuery);
            $sql->addWhereIn($filterFieldName, DB::subQuery($categorySubQuery));
        } else {
            ACMS_Filter::categoryStatus($sql, 'category');
            if (!is_null($cid)) {
                $sql->addWhereOpr('entry_category_id', null);
            }
        }
        return $multi;
    }

    /**
     * ユーザーの絞り込み
     *
     * @param SQL_Select $sql
     * @param string|int|null $uid
     *
     * @return bool
     */
    protected function userFilterQuery(SQL_Select $sql, string|int|null $uid)
    {
        $multi = false;
        if (!empty($uid)) {
            if (is_int($uid)) {
                $sql->addWhereOpr('entry_user_id', $uid);
            } elseif (strpos($uid, ',') !== false) {
                $sql->addWhereIn('entry_user_id', explode(',', $uid));
                $multi = true;
            }
        }
        return $multi;
    }

    /**
     * エントリーの絞り込み
     *
     * @param SQL_Select & $sql
     * @param string|int|null $eid
     *
     * @return bool
     */
    protected function entryFilterQuery(SQL_Select $sql, string|int|null $eid)
    {
        $multi = false;
        if (!empty($eid)) {
            if (is_int($eid)) {
                $sql->addWhereOpr('entry_id', $eid);
            } elseif (strpos($eid, ',') !== false) {
                $sql->addWhereIn('entry_id', explode(',', $eid));
                $multi = true;
            }
        }
        return $multi;
    }

    /**
     * ブログの絞り込み
     *
     * @param SQL_Select $sql
     * @param string|int $cid
     * @param string $axis
     * @param bool $multi
     * @param array $config
     *
     * @return void
     */
    protected function blogFilterQuery(
        SQL_Select $sql,
        string|int|null $bid,
        string $axis,
        array $config,
        bool $multi
    ) {
        if (!empty($bid) && is_int($bid) && $axis === 'self') {
            $sql->addWhereOpr('entry_blog_id', $bid);
            if ($config['secret'] === 'on') {
                ACMS_Filter::blogDisclosureSecretStatus($sql, 'blog');
            } else {
                ACMS_Filter::blogStatus($sql, 'blog');
            }
        } elseif (!empty($bid)) {
            $blogSubQuery = SQL::newSelect('blog');
            $blogSubQuery->setSelect('blog_id');
            if (is_int($bid)) {
                if ($multi) {
                    ACMS_Filter::blogTree($blogSubQuery, $bid, 'descendant-or-self');
                } else {
                    ACMS_Filter::blogTree($blogSubQuery, $bid, $axis);
                }
            } else {
                if (strpos($bid, ',') !== false) {
                    $blogSubQuery->addWhereIn('blog_id', explode(',', $bid));
                }
            }
            if ($config['secret'] === 'on') {
                ACMS_Filter::blogDisclosureSecretStatus($blogSubQuery);
            } else {
                ACMS_Filter::blogStatus($blogSubQuery);
            }
            $sql->addWhereIn('entry_blog_id', DB::subQuery($blogSubQuery));
        }
    }

    /**
     * タグの絞り込み
     *
     * @param SQL_Select $sql
     * @return void
     */
    protected function tagFilterQuery(SQL_Select $sql, $tags)
    {
        ACMS_Filter::entryTag($sql, $tags);
    }

    /**
     * キーワードの絞り込み
     *
     * @param SQL_Select $sql
     * @param string $keyword
     *
     * @return void
     */
    protected function keywordFilterQuery(SQL_Select $sql, string $keyword)
    {
        ACMS_Filter::entryKeyword($sql, $keyword);
    }

    /**
     * フィールドの絞り込み
     *
     * @param SQL_Select $sql
     * @param \Field $Field
     *
     * @return void
     */
    protected function fieldFilterQuery(SQL_Select $sql, \Field $Field): void
    {
        $sortFields = ACMS_Filter::entryField($sql, $Field);
        if (is_array($sortFields)) {
            foreach ($sortFields as $name) {
                $sql->addSelect($name);
            }
        }
    }

    /**
     * その他の絞り込み
     *
     * @param SQL_Select $sql
     * @param array $config
     *
     * @return void
     */
    protected function otherFilterQuery(SQL_Select $sql, $config)
    {
        if ($config['indexing'] === 'on') {
            $sql->addWhereOpr('entry_indexing', 'on');
        }
        if ($config['noimage'] !== 'on') {
            $sql->addWhereOpr('entry_primary_image', null, '<>');
        }
        if (EID && $config['hiddenCurrentEntry'] === 'on') {
            $sql->addWhereOpr('entry_id', EID, '<>');
        }
    }

    /**
     * EagerLoading
     *
     * @param array $entries
     * @param array $config
     *
     * @return void
     */
    protected function eagerLoad(array $entries, array $config)
    {
        $eargerLoad = [
            'relatedEntry' => [],
            'unit' => [],
            'media' => [],
            'module' => [],
            'tag' => [],
            'subCategory' => [],
            'entryField' => [],
            'userField' => [],
            'blogField' => [],
            'categoryField' => []
        ];

        // 関連エントリーのEagerLoading
        $eargerLoad['relatedEntry'] = $this->relatedEntryEagerLoad($entries, $config)

        // ユニットのEagerLoading
        $eargerLoad['unit'] = $this->unitEagerLoad($entries, $config);

        // モジュールのEagerLoading
        $eargerLoad['module'] = $this->moduleEagerLoad($unitData, $config);

        // メディアのEagerLoading
        $eargerLoad['media'] = $this->mediaEagerLoad(
            $entries,
            $eargerLoad['relatedEntry'],
            $eargerLoad['unit'],
            $config
        );

        // タグのEagerLoading
        $eargerLoad['tag'] = $this->tagEagerLoad(
            $entries,
            $eargerLoad['relatedEntry'],
            $config
        );

        // サブカテゴリーのEagerLoading
        $eargerLoad['subCategory'] = $this->subCategoryEagerLoad(
            $entries,
            $eargerLoad['relatedEntry'],
            $config
        );

        // エントリーフィールドのEagerLoading
        $eargerLoad['entryField'] = $this->entryFieldEagerLoad(
            $entries,
            $eargerLoad['relatedEntry'],
            $config
        );

        // ユーザーフィールドのEagerLoading
        $eargerLoad['userField'] = $this->userFieldEagerLoad(
            $entries,
            $eargerLoad['relatedEntry'],
            $config
        );

        // カテゴリーフィールドのEagerLoading
        $eargerLoad['categoryField'] = $this->categoryFieldEagerLoad(
            $entries,
            $eargerLoad['subCategory'],
            $eargerLoad['relatedEntry'],
            $config
        );

        // ブログフィールドのEagerLoading
        $eargerLoad['blogField'] = $this->blogFieldEagerLoad(
            $entries,
            $eargerLoad['subCategory'],
            $eargerLoad['relatedEntry'],
            $config
        );

        return $eargerLoad;
    }

    /**
     * 関連エントリーのEagerLoading
     *
     * @param array $entries
     * @param array $config
     *
     * @return array
     */
    protected function relatedEntryEagerLoad(array $entries, array $config): array
    {
        if (isset($config['relatedEntryOn']) && $config['relatedEntryOn'] === 'on') {
            $entryIds = array();
            foreach ($entries as $entry) {
                if (!empty($entry['entry_id'])) {
                    $entryIds[] = $entry['entry_id'];
                }
            }
            $sql = SQL::newSelect('relationship');
            $sql->addLeftJoin('entry', 'entry_id', 'relation_eid', 'entry');
            $sql->addLeftJoin('category', 'category_id', 'entry_category_id', 'category', 'entry');
            $sql->addLeftJoin('blog', 'blog_id', 'entry_blog_id', 'blog', 'entry');
            $sql->addLeftJoin('blog', 'blog_id', 'category_blog_id', 'category_blog', 'category');
            $sql->addLeftJoin('user', 'user_id', 'entry_user_id', 'user', 'entry');
            $sql->addLeftJoin('user', 'user_id', 'entry_last_update_user_id', 'last_update_user', 'entry');
            $sql->addLeftJoin('blog', 'blog_id', 'user_blog_id', 'user_blog', 'user');
            $sql->addLeftJoin('blog', 'blog_id', 'user_blog_id', 'last_update_user_blog', 'last_update_user');
            $sql->addLeftJoin('column', 'column_id', 'entry_primary_image', 'primary_image', 'entry');
            $sql->addLeftJoin('geo', 'geo_eid', 'entry_id', 'geo', 'entry');
            ACMS_Filter::entrySession($sql);
            $sql->addWhereIn('relation_id', $entryIds);
            $sql->setOrder('relation_order', 'ASC');
            foreach (
                [
                'relation_id',
                'relation_eid',
                'relation_type',
                'relation_order',
                ] as $field
            ) {
                $sql->addSelect($field);
            }
            $this->setSelect($sql);
            $relatedEntries = DB::query($sql->get(dsn()), 'all');

            $eagerLoadingData = [];
            foreach ($relatedEntries as $relatedEntry) {
                $eid = $relatedEntry['relation_id'];
                $type = $relatedEntry['relation_type'];
                if (!isset($eagerLoadingData[$eid])) {
                    $eagerLoadingData[$eid] = [];
                }
                if (!isset($eagerLoadingData[$eid][$type])) {
                    $eagerLoadingData[$eid][$type] = [];
                }
                $eagerLoadingData[$eid][$type][] = $relatedEntry;
            }
            return $eagerLoadingData;
        }
        return [];
    }

    /**
     * ユニットのEagerLoading
     *
     * @param array $entries
     * @param array $config
     *
     * @return array
     */
    protected function unitEagerLoad(array $entries, array $config): array
    {
        if (
            0 ||
            (isset($config['unitInfoOn']) && $config['unitInfoOn'] === 'on') ||
            (isset($config['fullTextOn']) && $config['fullTextOn'] === 'on')
        ) {
            $entryIds = array();
            foreach ($entries as $entry) {
                if (!empty($entry['entry_id'])) {
                    $entryIds[] = $entry['entry_id'];
                }
            }
            $sql = SQL::newSelect('column');
            $sql->addWhereIn('column_entry_id', array_unique($entryIds));
            $sql->addWhereOpr('column_attr', 'acms-form', '<>');
            $sql->setOrder('column_sort', 'ASC');

            return array_reduce(
                DB::query($sql->get(dsn()), 'all'),
                function (array $units, array $unit) {
                    if (!array_key_exists(intval($unit['column_entry_id']), $units)) {
                        return [
                            ...$units,
                            intval($unit['column_entry_id']) => [$unit]
                        ];
                    }

                    return [
                        ...$units,
                        intval($unit['column_entry_id']) => [
                            ...$units[intval($unit['column_entry_id'])],
                            $unit
                        ]
                    ];
                },
                []
            );
        }
        return [];
    }

    /**
     * モジュールのEagerLoading
     *
     * @param array $unitData
     * @param array $config
     *
     * @return array
     */
    protected function moduleEagerLoad(array $unitData, array $config): array
    {
        if (isset($config['unitInfoOn']) && $config['unitInfoOn'] === 'on') {
            $moduleIds = [];
            foreach ($unitData as $units) {
                foreach ($units as $unit) {
                    if ($unit['column_type'] === UnitType::MODULE) {
                        $moduleIds[] = intval($unit['column_field_1']);
                    }
                }
            }

            return array_reduce(
                $this->ModuleRepository->findByIds(array_unique($moduleIds)),
                function (array $modules, Module $Module) {
                    return [
                        ...$modules,
                        $Module->getId() => $Module
                    ];
                },
                []
            );
        }

        return [];
    }

    /**
     * メディアのEagerLoading
     *
     * @param array $entries
     * @param array $relatedEntryData
     * @param array $unitData
     * @param array $config
     *
     * @return array
     */
    protected function mediaEagerLoad(
        array $entries,
        array $relatedEntryData,
        array $unitData,
        array $config
    ): array {
        $mediaIds = [];
        if (isset($config['mainImageOn']) && $config['mainImageOn'] === 'on') {
            foreach ($entries as $entry) {
                if (
                    1 &&
                    !is_null($entry['entry_primary_image']) &&
                    $entry['primary_image_unit_type'] === UnitType::MEDIA
                ) {
                    $mediaIds[] = intval($entry['primary_image_unit_field_1']);
                }
            }
            foreach ($relatedEntryData as $relatedEntryGroup) {
                foreach ($relatedEntryGroup as $relatedEntries) {
                    foreach ($relatedEntries as $relatedEntry) {
                        if (
                            1 &&
                            !is_null($relatedEntry['entry_primary_image']) &&
                            $relatedEntry['primary_image_unit_type'] === UnitType::MEDIA
                        ) {
                            $mediaIds[] = intval($relatedEntry['primary_image_unit_field_1']);
                        }
                    }
                }
            }
        }
        if (isset($config['unitInfoOn']) && $config['unitInfoOn'] === 'on') {
            foreach ($unitData as $units) {
                foreach ($units as $unit) {
                    if ($unit['column_type'] === UnitType::MEDIA) {
                        $mediaIds[] = intval($unit['column_field_1']);
                    }
                }
            }
        }

        if (empty($mediaIds)) {
            return [];
        }

        $Medias = $this->MediaRepository->findByIds(array_unique($mediaIds));

        return array_reduce(
            $Medias,
            function (array $medias, Media $Media) {
                return [
                    ...$medias,
                    $Media->getId() => $Media
                ];
            },
            []
        );
    }

    /**
     * タグのEagerLoading
     *
     * @param array $entries
     * @param array $relatedEntryData
     * @param array $config
     *
     * @return array
     */
    protected function tagEagerLoad(
        array $entries,
        array $relatedEntryData,
        array $config
    ): array {
        if (isset($config['tagOn']) && $config['tagOn'] === 'on') {
            $entryIds = array();
            foreach ($entries as $entry) {
                if (!empty($entry['entry_id'])) {
                    $entryIds[] = $entry['entry_id'];
                }
            }
            foreach ($relatedEntryData as $relatedEntryGroup) {
                foreach ($relatedEntryGroup as $relatedEntries) {
                    foreach ($relatedEntries as $relatedEntry) {
                        if (!empty($relatedEntry['relation_eid'])) {
                            $entryIds[] = $relatedEntry['relation_eid'];
                        }
                    }
                }
            }
            $sql = SQL::newSelect('tag');
            $sql->addWhereIn('tag_entry_id', array_unique($entryIds));
            $sql->addOrder('tag_sort');
            $rows = DB::query($sql->get(dsn()), 'all');

            $eagerLoadingData = [];
            foreach ($rows as $row) {
                $eid = intval($row['tag_entry_id']);
                if (!isset($eagerLoadingData[$eid])) {
                    $eagerLoadingData[$eid] = array();
                }
                $eagerLoadingData[$eid][] = $row;
            }
            return $eagerLoadingData;
        }
        return [];
    }

    /**
     * サブカテゴリーのEagerLoading
     *
     * @param array $entries
     * @param array $relatedEntryData
     * @param array $config
     *
     * @return array
     */
    protected function subCategoryEagerLoad(
        array $entries,
        array $relatedEntriyData,
        array $config
    ): array {
        if (isset($config['categoryInfoOn']) && $config['categoryInfoOn'] === 'on') {
            $entryIds = [];
            foreach ($entries as $entry) {
                if (!empty($entry['entry_id'])) {
                    $entryIds[] = $entry['entry_id'];
                }
            }
            foreach ($relatedEntriyData as $relatedEntryGroup) {
                foreach ($relatedEntryGroup as $relatedEntries) {
                    foreach ($relatedEntries as $relatedEntry) {
                        if (!empty($relatedEntry['relation_eid'])) {
                            $entryIds[] = $relatedEntry['relation_eid'];
                        }
                    }
                }
            }
            $sql = SQL::newSelect('entry_sub_category');
            $sql->addLeftJoin('category', 'category_id', 'entry_sub_category_id');
            $sql->addLeftJoin('blog', 'blog_id', 'category_blog_id', 'category_blog');
            $sql->addWhereIn('entry_sub_category_eid', array_unique($entryIds));
            foreach (
                [
                [
                    'field' => '*',
                    'alias' => null,
                    'scope' => null,
                    'function' => null
                ],
                [
                    'field' => 'blog_id',
                    'alias' => 'category_blog_blog_id',
                    'scope' => 'category_blog',
                    'function' => null
                ],
                [
                    'field' => 'blog_code',
                    'alias' => 'category_blog_blog_code',
                    'scope' => 'category_blog',
                    'function' => null
                ],
                [
                    'field' => 'blog_status',
                    'alias' => 'category_blog_blog_status',
                    'scope' => 'category_blog',
                    'function' => null
                ],
                [
                    'field' => 'blog_parent',
                    'alias' => 'category_blog_blog_parent',
                    'scope' => 'category_blog',
                    'function' => null
                ],
                [
                    'field' => 'blog_sort',
                    'alias' => 'category_blog_blog_sort',
                    'scope' => 'category_blog',
                    'function' => null
                ],
                [
                    'field' => 'blog_left',
                    'alias' => 'category_blog_blog_left',
                    'scope' => 'category_blog',
                    'function' => null
                ],
                [
                    'field' => 'blog_right',
                    'alias' => 'category_blog_blog_right',
                    'scope' => 'category_blog',
                    'function' => null
                ],
                [
                    'field' => 'blog_name',
                    'alias' => 'category_blog_blog_name',
                    'scope' => 'category_blog',
                    'function' => null
                ],
                [
                    'field' => 'blog_domain',
                    'alias' => 'category_blog_blog_domain',
                    'scope' => 'category_blog',
                    'function' => null
                ],
                [
                    'field' => 'blog_indexing',
                    'alias' => 'category_blog_blog_indexing',
                    'scope' => 'category_blog',
                    'function' => null
                ],
                [
                    'field' => 'blog_generated_datetime',
                    'alias' => 'category_blog_blog_generated_datetime',
                    'scope' => 'category_blog',
                    'function' => null
                ],
                ] as $select
            ) {
                $sql->addSelect(
                    $select['field'],
                    $select['alias'],
                    $select['scope'],
                    $select['function']
                );
            }

            $rows = DB::query($sql->get(dsn()), 'all');

            $eagerLoadingData = [];
            foreach ($rows as $row) {
                $eid = intval($row['entry_sub_category_eid']);
                if (!isset($eagerLoadingData[$eid])) {
                    $eagerLoadingData[$eid] = [];
                }
                $eagerLoadingData[$eid][] = $row;
            }
            return $eagerLoadingData;
        }
        return [];
    }

    /**
     * エントリーフィールドのEagerLoading
     *
     * @param array $entries
     * @param array $relatedEntryData
     * @param array $config
     *
     * @return array
     */
    protected function entryFieldEagerLoad(
        array $entries,
        array $relatedEntriyData,
        array $config
    ): array {
        if (!isset($config['entryFieldOn']) || $config['entryFieldOn'] === 'on') {
            $entryIds = array();
            foreach ($entries as $entry) {
                if (!empty($entry['entry_id'])) {
                    $entryIds[] = $entry['entry_id'];
                }
            }
            foreach ($relatedEntriyData as $relatedEntryGroup) {
                foreach ($relatedEntryGroup as $relatedEntries) {
                    foreach ($relatedEntries as $relatedEntry) {
                        if (!empty($relatedEntry['relation_eid'])) {
                            $entryIds[] = $relatedEntry['relation_eid'];
                        }
                    }
                }
            }
            return eagerLoadField(array_unique($entryIds), 'eid');
        }
        return [];
    }

    /**
     * ユーザーフィールドのEagerLoading
     *
     * @param array $entries
     * @param array $relatedEntryData
     * @param array $config
     *
     * @return array
     */
    protected function userFieldEagerLoad(
        array $entries,
        array $relatedEntriyData,
        array $config
    ): array {
        if (isset($config['userInfoOn']) && $config['userInfoOn'] === 'on') {
            $userIds = array();
            foreach ($entries as $entry) {
                if (!empty($entry['entry_user_id'])) {
                    $userIds[] = $entry['entry_user_id'];
                }
                if (!empty($entry['entry_last_update_user_id'])) {
                    $userIds[] = $entry['entry_last_update_user_id'];
                }
            }
            foreach ($relatedEntriyData as $relatedEntryGroup) {
                foreach ($relatedEntryGroup as $relatedEntries) {
                    foreach ($relatedEntries as $relatedEntry) {
                        if (!empty($relatedEntry['entry_user_id'])) {
                            $userIds[] = $relatedEntry['entry_user_id'];
                        }
                        if (!empty($relatedEntry['entry_last_update_user_id'])) {
                            $userIds[] = $relatedEntry['entry_last_update_user_id'];
                        }
                    }
                }
            }
            return eagerLoadField(array_unique($userIds), 'uid');
        }
        return [];
    }

    /**
     * カテゴリーフィールドのEagerLoading
     *
     * @param array $entries
     * @param array $subCategoryData
     * @param array $relatedEntryData
     * @param array $config
     *
     * @return array
     */
    protected function categoryFieldEagerLoad(
        array $entries,
        array $subCategoryData,
        array $relatedEntriyData,
        array $config
    ): array {
        if (isset($config['categoryInfoOn']) && $config['categoryInfoOn'] === 'on') {
            $categoryIds = array();
            foreach ($entries as $entry) {
                if (!empty($entry['entry_category_id'])) {
                    $categoryIds[] = $entry['entry_category_id'];
                }
            }
            foreach (array_values($subCategoryData) as $subCategorys) {
                foreach ($subCategorys as $subCategory) {
                    if (!empty($subCategory['entry_sub_category_id'])) {
                        $categoryIds[] = $subCategory['entry_sub_category_id'];
                    }
                }
            }
            foreach ($relatedEntriyData as $relatedEntryGroup) {
                foreach ($relatedEntryGroup as $relatedEntries) {
                    foreach ($relatedEntries as $relatedEntry) {
                        if (!empty($relatedEntry['entry_category_id'])) {
                            $categoryIds[] = $relatedEntry['entry_category_id'];
                        }
                    }
                }
            }
            return eagerLoadField(array_unique($categoryIds), 'cid');
        }
        return [];
    }

    /**
     * ブログフィールドのEagerLoading
     *
     * @param array $entries
     * @param array $relatedEntryData
     * @param array $subCategoryData
     * @param array $config
     *
     * @return array
     */
    protected function blogFieldEagerLoad(
        array $entries,
        array $relatedEntriyData,
        array $subCategoryData,
        array $config
    ): array {
        if (isset($config['blogInfoOn']) && $config['blogInfoOn'] === 'on') {
            $blogIds = array();
            foreach ($entries as $entry) {
                if (!empty($entry['entry_blog_id'])) {
                    $blogIds[] = $entry['entry_blog_id'];
                }
                if (!empty($entry['last_update_user_blog_blog_id'])) {
                    $blogIds[] = $entry['last_update_user_blog_blog_id'];
                }
                if (!empty($entry['category_blog_id'])) {
                    $blogIds[] = $entry['category_blog_id'];
                }
            }
            foreach ($relatedEntriyData as $relatedEntryGroup) {
                foreach ($relatedEntryGroup as $relatedEntries) {
                    foreach ($relatedEntries as $relatedEntry) {
                        if (!empty($relatedEntry['entry_blog_id'])) {
                            $blogIds[] = $relatedEntry['entry_blog_id'];
                        }
                        if (!empty($relatedEntry['last_update_user_blog_blog_id'])) {
                            $blogIds[] = $relatedEntry['last_update_user_blog_blog_id'];
                        }
                        if (!empty($relatedEntry['category_blog_id'])) {
                            $blogIds[] = $relatedEntry['category_blog_id'];
                        }
                    }
                }
            }
            foreach (array_values($subCategoryData) as $subCategorys) {
                foreach ($subCategorys as $subCategory) {
                    if (!empty($subCategory['category_blog_id'])) {
                        $blogIds[] = $subCategory['category_blog_id'];
                    }
                }
            }
            return eagerLoadField(array_unique($blogIds), 'bid');
        }
        return [];
    }

    /**
     * Entryオブジェクトの作成
     *
     * @param array $row
     * @param array $eagerLoadingData
     *
     * @return Entry
     */
    protected function createEntry(
        array $row,
        array $entryField = [],
        array $userField = [],
        array $categoryField = [],
        array $blogField = [],
        array $media = [],
        array $tag = [],
        array $subCategory = [],
        array $relatedEntry = [],
        array $module = [],
        array $unit = []
    ) {
        $Entry = new Entry();
        $Entry->setId(intval($row['entry_id']));
        $Entry->setCode($row['entry_code']);
        $Entry->setStatus($row['entry_status']);
        $Entry->setApproval($row['entry_approval']);
        $Entry->setFormStatus($row['entry_form_status']);
        $Entry->setSort(intval($row['entry_sort']));
        $Entry->setUserSort(intval($row['entry_user_sort']));
        $Entry->setCategorySort(intval($row['entry_category_sort']));
        $Entry->setTitle($row['entry_title']);
        $Entry->setLink($row['entry_link']);
        $Entry->setDatetime(new DateTimeImmutable($row['entry_datetime']));
        $Entry->setStartDatetime(new DateTimeImmutable($row['entry_start_datetime']));
        $Entry->setEndDatetime(new DateTimeImmutable($row['entry_end_datetime']));
        $Entry->setPostedDatetime(new DateTimeImmutable($row['entry_posted_datetime']));
        $Entry->setUpdatedDatetime(new DateTimeImmutable($row['entry_updated_datetime']));
        $Entry->setSummaryRange(
            !is_null($row['entry_summary_range']) ? intval($row['entry_summary_range']) : null
        );
        $Entry->setIndexing($row['entry_indexing']);
        $Entry->setCurrentRevId(intval($row['entry_current_rev_id']));
        $Entry->setHash($row['entry_hash']);
        $Entry->setFormId(intval($row['entry_form_id']));

        if (array_key_exists(intval($row['entry_id']), $entryField)) {
            $Entry->setField($entryField[intval($row['entry_id'])]);
        }

        if (!is_null($row['entry_primary_image'])) {
            $PrimaryImageUnit = new Unit();
            $PrimaryImageUnit->setId(intval($row['primary_image_unit_id']));
            $PrimaryImageUnit->setSort(intval($row['primary_image_unit_sort']));
            $PrimaryImageUnit->setAlign($row['primary_image_unit_align']);
            $PrimaryImageUnit->setType($row['primary_image_unit_type']);
            $PrimaryImageUnit->setAtrr($row['primary_image_unit_attr']);
            $PrimaryImageUnit->setGroup($row['primary_image_unit_group']);
            $PrimaryImageUnit->setSize(
                is_numeric($row['primary_image_unit_size'])
                    ? floatval($row['primary_image_unit_size'])
                    : $row['primary_image_unit_size']
            );
            $PrimaryImageUnit->setField1(
                ($row['primary_image_unit_type'] === UnitType::MEDIA &&
                    array_key_exists(intval($row['primary_image_unit_field_1']), $media)
                ) ? $media[intval($row['primary_image_unit_field_1'])]
                    : $row['primary_image_unit_field_1']
            );
            $PrimaryImageUnit->setField2($row['primary_image_unit_field_2']);
            $PrimaryImageUnit->setField3($row['primary_image_unit_field_3']);
            $PrimaryImageUnit->setField4($row['primary_image_unit_field_4']);
            $PrimaryImageUnit->setField5($row['primary_image_unit_field_5']);
            $PrimaryImageUnit->setField6($row['primary_image_unit_field_6']);
            $PrimaryImageUnit->setField7($row['primary_image_unit_field_7']);
            $PrimaryImageUnit->setField8($row['primary_image_unit_field_8']);
            $PrimaryImageUnit->setEntry($this->createEntry(
                [...$row, 'entry_primary_image' => null],
                $entryField,
                $categoryField,
                $blogField,
            ));
            $Entry->setPrimaryImage($PrimaryImageUnit);
        }

        $Blog = new Blog();
        $Blog->setId(intval($row['blog_id']));
        $Blog->setCode($row['blog_code']);
        $Blog->setStatus($row['blog_status']);
        $Blog->setSort(intval($row['blog_sort']));
        $Blog->setBlogParent(intval($row['blog_parent']));
        $Blog->setName($row['blog_name']);
        $Blog->setLeft(intval($row['blog_left']));
        $Blog->setRight(intval($row['blog_right']));
        $Blog->setDomain($row['blog_domain']);
        $Blog->setGeneratedDatetime(new DateTimeImmutable($row['blog_generated_datetime']));
        $Blog->setIndexing($row['blog_indexing']);
        if (array_key_exists(intval($row['blog_id']), $blogField)) {
            $Blog->setField($blogField[intval($row['blog_id'])]);
        }

        $Entry->setBlog($Blog);

        $UserBlog = new Blog();
        $UserBlog->setId(intval($row['user_blog_blog_id']));
        $UserBlog->setCode($row['user_blog_blog_code']);
        $UserBlog->setStatus($row['user_blog_blog_status']);
        $UserBlog->setSort(intval($row['user_blog_blog_sort']));
        $UserBlog->setBlogParent(intval($row['user_blog_blog_parent']));
        $UserBlog->setName($row['user_blog_blog_name']);
        $UserBlog->setLeft(intval($row['user_blog_blog_left']));
        $UserBlog->setRight(intval($row['user_blog_blog_right']));
        $UserBlog->setDomain($row['user_blog_blog_domain']);
        $UserBlog->setGeneratedDatetime(new DateTimeImmutable($row['user_blog_blog_generated_datetime']));
        $UserBlog->setIndexing($row['user_blog_blog_indexing']);
        if (array_key_exists(intval($row['user_blog_blog_id']), $blogField)) {
            $UserBlog->setField($blogField[intval($row['user_blog_blog_id'])]);
        }

        $User = new User();
        $User->setId(intval($row['user_id']));
        $User->setCode($row['user_code']);
        $User->setStatus($row['user_status']);
        $User->setSort(intval($row['user_sort']));
        $User->setName($row['user_name']);
        $User->setMail($row['user_mail']);
        $User->setUrl($row['user_url']);
        $User->setIcon($row['user_icon']);
        $User->setAuth($row['user_auth']);
        $User->setGeneratedDatetime(new DateTimeImmutable($row['user_generated_datetime']));
        $User->setUpdatedDatetime(new DateTimeImmutable($row['user_updated_datetime']));
        $User->setIndexing($row['user_indexing']);
        $User->setBlog($UserBlog);
        if (array_key_exists(intval($row['user_id']), $userField)) {
            $User->setField($userField[intval($row['user_id'])]);
        }

        $Entry->setUser($User);

        $LastUpdateUserBlog = new Blog();
        $LastUpdateUserBlog->setId(intval($row['last_update_user_blog_blog_id']));
        $LastUpdateUserBlog->setCode($row['last_update_user_blog_blog_code']);
        $LastUpdateUserBlog->setStatus($row['last_update_user_blog_blog_status']);
        $LastUpdateUserBlog->setSort(intval($row['last_update_user_blog_blog_sort']));
        $LastUpdateUserBlog->setBlogParent(intval($row['last_update_user_blog_blog_parent']));
        $LastUpdateUserBlog->setName($row['last_update_user_blog_blog_name']);
        $LastUpdateUserBlog->setLeft(intval($row['last_update_user_blog_blog_left']));
        $LastUpdateUserBlog->setRight(intval($row['last_update_user_blog_blog_right']));
        $LastUpdateUserBlog->setDomain($row['last_update_user_blog_blog_domain']);
        $LastUpdateUserBlog->setGeneratedDatetime(
            new DateTimeImmutable($row['last_update_user_blog_blog_generated_datetime'])
        );
        $LastUpdateUserBlog->setIndexing($row['last_update_user_blog_blog_indexing']);
        if (array_key_exists(intval($row['last_update_user_blog_blog_id']), $blogField)) {
            $LastUpdateUserBlog->setField($blogField[intval($row['last_update_user_blog_blog_id'])]);
        }

        $LastUpdateUser = new User();
        $LastUpdateUser->setId(intval($row['last_update_user_user_id']));
        $LastUpdateUser->setCode($row['last_update_user_user_code']);
        $LastUpdateUser->setStatus($row['last_update_user_user_status']);
        $LastUpdateUser->setSort(intval($row['last_update_user_user_sort']));
        $LastUpdateUser->setName($row['last_update_user_user_name']);
        $LastUpdateUser->setMail($row['last_update_user_user_mail']);
        $LastUpdateUser->setUrl($row['last_update_user_user_url']);
        $LastUpdateUser->setIcon($row['last_update_user_user_icon']);
        $LastUpdateUser->setAuth($row['last_update_user_user_auth']);
        $LastUpdateUser->setGeneratedDatetime(new DateTimeImmutable($row['last_update_user_user_generated_datetime']));
        $LastUpdateUser->setUpdatedDatetime(new DateTimeImmutable($row['last_update_user_user_updated_datetime']));
        $LastUpdateUser->setIndexing($row['last_update_user_user_indexing']);
        $LastUpdateUser->setBlog($LastUpdateUserBlog);
        if (array_key_exists(intval($row['last_update_user_user_id']), $userField)) {
            $LastUpdateUser->setField($userField[intval($row['last_update_user_user_id'])]);
        }

        $Entry->setLastUpdateUser($LastUpdateUser);

        if (!is_null($row['entry_category_id'])) {
            $CategoryBlog = new Blog();
            $CategoryBlog->setId(intval($row['category_blog_blog_id']));
            $CategoryBlog->setCode($row['category_blog_blog_code']);
            $CategoryBlog->setStatus($row['category_blog_blog_status']);
            $CategoryBlog->setSort(intval($row['category_blog_blog_sort']));
            $CategoryBlog->setBlogParent(intval($row['category_blog_blog_parent']));
            $CategoryBlog->setName($row['category_blog_blog_name']);
            $CategoryBlog->setLeft(intval($row['category_blog_blog_left']));
            $CategoryBlog->setRight(intval($row['category_blog_blog_right']));
            $CategoryBlog->setDomain($row['category_blog_blog_domain']);
            $CategoryBlog->setGeneratedDatetime(new DateTimeImmutable($row['category_blog_blog_generated_datetime']));
            $CategoryBlog->setIndexing($row['category_blog_blog_indexing']);
            if (array_key_exists(intval($row['category_blog_blog_id']), $blogField)) {
                $CategoryBlog->setField($blogField[intval($row['category_blog_blog_id'])]);
            }

            $Category = new Category();
            $Category->setId(intval($row['category_id']));
            $Category->setCode($row['category_code']);
            $Category->setStatus($row['category_status']);
            $Category->setCategoryParent(intval($row['category_parent']));
            $Category->setSort(intval($row['category_sort']));
            $Category->setName($row['category_name']);
            $Category->setRight(intval($row['category_left']));
            $Category->setRight(intval($row['category_right']));
            $Category->setScope($row['category_scope']);
            $Category->setIndexing($row['category_indexing']);
            $Category->setBlog($CategoryBlog);
            if (array_key_exists(intval($row['category_id']), $categoryField)) {
                $Category->setField($categoryField[intval($row['category_id'])]);
            }

            $Entry->setCategory($Category);
        }

        if (array_key_exists(intval($row['entry_id']), $tag)) {
            $Tags = array_map(
                function (array $tag) use ($Entry) {
                    $Tag = new Tag();
                    $Tag->setName($tag['tag_name']);
                    $Tag->setSort(intval($tag['tag_sort']));
                    $Tag->setEntry($Entry);

                    return $Tag;
                },
                $tag[intval($row['entry_id'])]
            );
            $Entry->setTags($Tags);
        }

        if (array_key_exists(intval($row['entry_id']), $unit)) {
            $UnitEntry = $this->createEntry(
                [...$row, 'entry_primary_image' => null],
                $entryField,
                $categoryField,
                $blogField,
            );
            $Units = array_map(
                function (array $unit) use ($UnitEntry, $media, $module) {
                    $Unit = new Unit();
                    $Unit->setId(intval($unit['column_id']));
                    $Unit->setSort(intval($unit['column_sort']));
                    $Unit->setAlign($unit['column_align']);
                    $Unit->setType($unit['column_type']);
                    $Unit->setAtrr($unit['column_attr']);
                    $Unit->setGroup($unit['column_group']);
                    $Unit->setSize(
                        is_numeric($unit['column_size'])
                            ? floatval($unit['column_size'])
                            : $unit['column_size']
                    );
                    if (
                        1 &&
                        $unit['column_type'] === UnitType::MEDIA &&
                        array_key_exists(intval($unit['column_field_1']), $media)
                    ) {
                        $Unit->setField1($media[intval($unit['column_field_1'])]);
                    } elseif (
                        1 &&
                        $unit['column_type'] === UnitType::MODULE &&
                        array_key_exists(intval($unit['column_field_1']), $module)
                    ) {
                        $Unit->setField1($module[intval($unit['column_field_1'])]);
                    } else {
                        $Unit->setField1($unit['column_field_1']);
                    }
                    $Unit->setField2($unit['column_field_2']);
                    $Unit->setField3($unit['column_field_3']);
                    $Unit->setField4($unit['column_field_4']);
                    $Unit->setField5($unit['column_field_5']);
                    $Unit->setField6($unit['column_field_6']);
                    $Unit->setField7($unit['column_field_7']);
                    $Unit->setField8($unit['column_field_8']);
                    $Unit->setEntry($UnitEntry);

                    return $Unit;
                },
                $unit[intval($row['entry_id'])]
            );
            $Entry->setUnits($Units);
        }

        if (array_key_exists(intval($row['entry_id']), $subCategory)) {
            $SubCategories = array_map(
                function (array $subCategory) use ($categoryField, $blogField) {
                    $SubCategoryBlog = new Blog();
                    $SubCategoryBlog->setId(intval($subCategory['category_blog_blog_id']));
                    $SubCategoryBlog->setCode($subCategory['category_blog_blog_code']);
                    $SubCategoryBlog->setStatus($subCategory['category_blog_blog_status']);
                    $SubCategoryBlog->setSort(intval($subCategory['category_blog_blog_sort']));
                    $SubCategoryBlog->setBlogParent(intval($subCategory['category_blog_blog_parent']));
                    $SubCategoryBlog->setName($subCategory['category_blog_blog_name']);
                    $SubCategoryBlog->setLeft(intval($subCategory['category_blog_blog_left']));
                    $SubCategoryBlog->setRight(intval($subCategory['category_blog_blog_right']));
                    $SubCategoryBlog->setDomain($subCategory['category_blog_blog_domain']);
                    $SubCategoryBlog->setGeneratedDatetime(
                        new DateTimeImmutable($subCategory['category_blog_blog_generated_datetime'])
                    );
                    $SubCategoryBlog->setIndexing($subCategory['category_blog_blog_indexing']);
                    if (array_key_exists(intval($subCategory['category_blog_blog_id']), $blogField)) {
                        $SubCategoryBlog->setField($blogField[intval($subCategory['category_blog_blog_id'])]);
                    }

                    $SubCategory = new Category();
                    $SubCategory->setId(intval($subCategory['category_id']));
                    $SubCategory->setCode($subCategory['category_code']);
                    $SubCategory->setStatus($subCategory['category_status']);
                    $SubCategory->setCategoryParent(intval($subCategory['category_parent']));
                    $SubCategory->setSort(intval($subCategory['category_sort']));
                    $SubCategory->setName($subCategory['category_name']);
                    $SubCategory->setRight(intval($subCategory['category_left']));
                    $SubCategory->setRight(intval($subCategory['category_right']));
                    $SubCategory->setScope($subCategory['category_scope']);
                    $SubCategory->setIndexing($subCategory['category_indexing']);
                    $SubCategory->setBlog($SubCategoryBlog);
                    if (array_key_exists(intval($subCategory['category_id']), $categoryField)) {
                        $SubCategory->setField($categoryField[intval($subCategory['category_id'])]);
                    }

                    return $SubCategory;
                },
                $subCategory[intval($row['entry_id'])]
            );
            $Entry->setSubCategories($SubCategories);
        }

        if (
            array_key_exists('latitude', $row) &&
            array_key_exists('longitude', $row) &&
            !is_null($row['latitude']) &&
            !is_null($row['longitude'])
        ) {
            $Geo = new Geo();
            $Geo->setZoom(intval($row['geo_zoom']));
            $Geo->setLatitude(floatval($row['latitude']));
            $Geo->setLongitude(floatval($row['longitude']));

            $Entry->setGeo($Geo);
        }

        if (array_key_exists(intval($row['entry_id']), $relatedEntry)) {
            $RelatedEntryGroups = array_map(
                function (
                    string $type,
                    array $entries
                ) use (
                    $entryField,
                    $userField,
                    $categoryField,
                    $blogField,
                    $tag,
                    $subCategory,
                    $media
                ) {
                    $Entries = array_map(
                        function (
                            array $entry
                        ) use (
                            $entryField,
                            $userField,
                            $categoryField,
                            $blogField,
                            $tag,
                            $subCategory,
                            $media
                        ) {
                            return $this->createEntry(
                                $entry,
                                $entryField,
                                $userField,
                                $categoryField,
                                $blogField,
                                $media,
                                $tag,
                                $subCategory
                            );
                        },
                        $entries
                    );

                    $RelatedEntryGroup = new RelatedEntryGroup();
                    $RelatedEntryGroup->setType($type);
                    $RelatedEntryGroup->setEntries($Entries);

                    return $RelatedEntryGroup;
                },
                array_keys($relatedEntry[intval($row['entry_id'])]),
                array_values($relatedEntry[intval($row['entry_id'])])
            );
            $Entry->setRelatedEntryGroups($RelatedEntryGroups);
        }

        return $Entry;
    }
}
