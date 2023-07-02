<?php

namespace Acms\Plugins\V2\GET\V2\Entry;

use Acms\Plugins\V2\GET\V2\AbstractEntry;

class Body extends AbstractEntry
{
    public $_axis = [
        'bid' => 'self',
        'cid' => 'self',
    ];

    /**
     * コンフィグの取得
     *
     * @return array
     */
    protected function initVars()
    {
        return [
            ...parent::initVars(),
            'order' => [
                $this->order ? $this->order : config('v2_entry_body_order'),
                config('v2_entry_body_order2'),
            ],
            'orderFieldName'        => config('v2_entry_body_order_field_name'),
            'noNarrowDownSort'      => config('v2_entry_body_no_narrow_down_sort', 'off'),
            'limit'                 => intval(config('v2_entry_body_limit')),
            'offset'                => intval(config('v2_entry_body_offset')),
            'indexing'              => config('v2_entry_body_indexing'),
            'subCategory'           => config('v2_entry_body_sub_category'),
            'secret'                => config('v2_entry_body_secret'),
            'notfound'              => config('mo_v2_entry_body_notfound'),
            'notfoundStatus404'     => config('v2_entry_body_notfound_status_404'),
            'newtime'               => intval(config('v2_entry_body_newtime')),
            'hiddenCurrentEntry'    => config('v2_entry_body_hidden_current_entry'),
            'hiddenPrivateEntry'    => config('v2_entry_body_hidden_private_entry'),
            'summaryRange'          => config('v2_entry_body_fix_summary_range'),
            'showAllIndex'          => config('v2_entry_body_show_all_index'),

            'pagerOn'               => config('v2_entry_body_pager_on'),
            'pagerDelta'            => intval(config('v2_entry_body_pager_delta')),
            'pagerCurAttr'          => config('v2_entry_body_pager_cur_attr'),

            'serialEntryOn'          => config('v2_entry_body_serial_entry_on'),
            'serialEntryIgnoreCategory' => config('v2_entry_body_serial_entry_ignore_category'),

            'microPager'            => config('v2_entry_body_micropage'),
            'microPagerDelta'       => intval(config('v2_entry_body_micropager_delta')),
            'microPagerCurAttr'     => config('v2_entry_body_micropager_cur_attr'),

            'mainImageOn'           => config('v2_entry_body_image_on'),
            'noimage'               => config('v2_entry_body_noimage'),
            'imageX'                => intval(config('v2_entry_body_image_x')),
            'imageY'                => intval(config('v2_entry_body_image_y')),
            'imageTrim'             => config('v2_entry_body_image_trim'),
            'imageZoom'             => config('v2_entry_body_image_zoom'),
            'imageCenter'           => config('v2_entry_body_image_center'),

            'entryFieldOn'          => config('v2_entry_body_entry_field_on'),
            'relatedEntryOn'        => config('v2_entry_body_related_entry_on', 'off'),
            'categoryInfoOn'        => config('v2_entry_body_category_on'),
            'categoryFieldOn'       => config('v2_entry_body_category_field_on'),
            'userInfoOn'            => config('v2_entry_body_user_on'),
            'userFieldOn'           => config('v2_entry_body_user_field_on'),
            'blogInfoOn'            => config('v2_entry_body_blog_on'),
            'blogFieldOn'           => config('v2_entry_body_blog_field_on'),
            'unitInfoOn'            => config('v2_entry_body_unit_on', 'on'),
            'detailDateOn'          => config('v2_entry_body_detail_date_on'),
            'fullTextOn'            => config('v2_entry_body_fulltext'),
            'fulltextWidth'         => config('v2_entry_body_fulltext_width'),
            'fulltextMarker'        => config('v2_entry_body_fulltext_marker'),
            'tagOn'                 => config('v2_entry_body_tag'),
            'loopClass'             => config('v2_entry_body_loop_class'),
            'imageViewer'            => config('v2_entry_body_image_viewer'),
        ];
    }
}
