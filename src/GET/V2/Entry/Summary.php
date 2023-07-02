<?php

namespace Acms\Plugins\V2\GET\V2\Entry;

use Acms\Plugins\V2\GET\V2\AbstractEntry;

class Summary extends AbstractEntry
{
    public $_axis = [ // phpcs:ignore
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
                $this->order ? $this->order : config('v2_entry_summary_order'),
                config('v2_entry_summary_order2'),
            ],
            'orderFieldName'        => config('v2_entry_summary_order_field_name'),
            'noNarrowDownSort'      => config('v2_entry_summary_no_narrow_down_sort', 'off'),
            'limit'                 => intval(config('v2_entry_summary_limit')),
            'offset'                => intval(config('v2_entry_summary_offset')),
            'indexing'              => config('v2_entry_summary_indexing'),
            'subCategory'           => config('v2_entry_summary_sub_category'),
            'secret'                => config('v2_entry_summary_secret'),
            'notfound'              => config('mo_v2_entry_summary_notfound'),
            'notfoundStatus404'     => config('v2_entry_summary_notfound_status_404'),
            'newtime'               => intval(config('v2_entry_summary_newtime')),
            'hiddenCurrentEntry'    => config('v2_entry_summary_hidden_current_entry'),
            'hiddenPrivateEntry'    => config('v2_entry_summary_hidden_private_entry'),

            'pagerOn'               => config('v2_entry_summary_pager_on'),
            'pagerDelta'            => intval(config('v2_entry_summary_pager_delta')),
            'pagerCurAttr'          => config('v2_entry_summary_pager_cur_attr'),

            'serialEntryOn'          => 'off',
            'serialEntryIgnoreCategory' => 'off',

            'microPager'            => 'off',
            'microPagerDelta'       => 'off',
            'microPagerCurAttr'     => 'off',

            'mainImageOn'           => config('v2_entry_summary_image_on'),
            'noimage'               => config('v2_entry_summary_noimage'),
            'imageX'                => intval(config('v2_entry_summary_image_x')),
            'imageY'                => intval(config('v2_entry_summary_image_y')),
            'imageTrim'             => config('v2_entry_summary_image_trim'),
            'imageZoom'             => config('v2_entry_summary_image_zoom'),
            'imageCenter'           => config('v2_entry_summary_image_center'),

            'entryFieldOn'          => config('v2_entry_summary_entry_field_on'),
            'relatedEntryOn'        => config('v2_entry_summary_related_entry_on', 'off'),
            'categoryInfoOn'        => config('v2_entry_summary_category_on'),
            'categoryFieldOn'       => config('v2_entry_summary_category_field_on'),
            'userInfoOn'            => config('v2_entry_summary_user_on'),
            'userFieldOn'           => config('v2_entry_summary_user_field_on'),
            'blogInfoOn'            => config('v2_entry_summary_blog_on'),
            'blogFieldOn'           => config('v2_entry_summary_blog_field_on'),
            'unitInfoOn'            => 'off',
            'detailDateOn'          => config('v2_entry_summary_detail_date_on'),
            'fullTextOn'            => config('v2_entry_summary_fulltext'),
            'fulltextWidth'         => config('v2_entry_summary_fulltext_width'),
            'fulltextMarker'        => config('v2_entry_summary_fulltext_marker'),
            'tagOn'                 => config('v2_entry_summary_tag'),
            'loopClass'             => config('v2_entry_summary_loop_class'),
        ];
    }
}
