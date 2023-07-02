<?php

namespace Acms\Plugins\V2\Repositories;

use DateTimeImmutable;
use Acms\Plugins\V2\Entities\Blog;
use Acms\Plugins\V2\Entities\Module;
use SQL;
use DB;

/**
 * モジュールのリポジトリ
 */
class ModuleRepository
{
    /**
     * 初期化処理
     *
     * @param \Acms\Services\Container
     */
    public function __construct(\Acms\Services\Container $container)
    {
    }

    /**
     * 指定したモジュールIDのメディアを取得する
     * @param int[] $mids
     *
     * @return Module[]
     */
    public function findByIds(array $mids): array
    {
        return array_map(
            function (array $row) {
                return $this->createModule($row);
            },
            DB::query($this->buildQuery($mids), 'all')
        );
    }

    /**
     * sqlの組み立て
     *
     * @param int[] $mids
     *
     * @return string
     */
    private function buildQuery(array $mids): string
    {
        $sql = SQL::newSelect('module');
        $sql->addLeftJoin('blog', 'blog_id', 'module_blog_id');
        $sql->addWhereIn('module_id', array_unique($mids));

        return $sql->get(dsn());
    }

    /**
     * モジュールオブジェクトの組み立て
     *
     * @param array $row
     *
     * @return Module
     */
    private function createModule(array $row): Module
    {
        $Module = new Module();
        $Module->setId(intval($row['module_id']));
        $Module->setIdentifier($row['module_identifier']);
        $Module->setName($row['module_name']);
        $Module->setLabel($row['module_label']);
        $Module->setDescription($row['module_description']);
        $Module->setStatus($row['module_status']);
        $Module->setScope($row['module_scope']);
        $Module->setCache(intval($row['module_cache']));
        $Module->setBid(
            is_numeric($row['module_bid']) ? intval($row['module_bid']) : $row['module_bid']
        );
        $Module->setBidAxis($row['module_bid_axis']);
        $Module->setUid(
            is_numeric($row['module_uid']) ? intval($row['module_uid']) : $row['module_uid']
        );
        $Module->setUidScope($row['module_uid_scope']);
        $Module->setCid(
            is_numeric($row['module_cid']) ? intval($row['module_cid']) : $row['module_cid']
        );
        $Module->setCidScope($row['module_cid_scope']);
        $Module->setCidAxis($row['module_cid_axis']);
        $Module->setEid(
            is_numeric($row['module_eid']) ? intval($row['module_eid']) : $row['module_eid']
        );
        $Module->setEidScope($row['module_eid_scope']);
        $Module->setKeyword($row['module_keyword']);
        $Module->setKeywordScope($row['module_keyword_scope']);
        $Module->setTag($row['module_tag']);
        $Module->setTagScope($row['module_tag_scope']);
        $Module->setField($row['module_field']);
        $Module->setFieldScope($row['module_field_scope']);
        $Module->setStart(
            !is_null($row['module_start']) ? new DateTimeImmutable($row['module_start']) : null
        );
        $Module->setStartScope($row['module_start_scope']);
        $Module->setEnd(
            !is_null($row['module_end']) ? new DateTimeImmutable($row['module_end']) : null
        );
        $Module->setEndScope($row['module_end_scope']);
        $Module->setPage(
            !empty($row['module_page']) ? $row['module_page'] : null
        );
        $Module->setPageScope($row['module_page_scope']);
        $Module->setOrder($row['module_order']);
        $Module->setOrderScope($row['module_order_scope']);
        $Module->setCustomField($row['module_custom_field']);
        $Module->setLayoutUse($row['module_layout_use']);
        $Module->setApiUse($row['module_api_use']);

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

        $Module->setBlog($Blog);

        return $Module;
    }
}
