<?php

namespace Laraditz\TikTok\Tests\Unit;

use Laraditz\TikTok\Enums\EventType;
use PHPUnit\Framework\TestCase;

class EventTypeTest extends TestCase
{
    public function test_enum_matches_tiktok_event_list()
    {
        $expected = [
            1 => 'ORDER_STATUS_CHANGE',
            2 => 'REVERSE_STATUS_UPDATE',
            3 => 'RECIPIENT_ADDRESS_UPDATE',
            4 => 'PACKAGE_UPDATE',
            5 => 'PRODUCT_STATUS_CHANGE',
            6 => 'SELLER_DEAUTHORIZATION',
            7 => 'AUTH_EXPIRE',
            11 => 'CANCELLATION_STATUS_CHANGE',
            12 => 'RETURN_STATUS_CHANGE',
            13 => 'NEW_CONVERSATION',
            14 => 'NEW_MESSAGE',
            15 => 'PRODUCT_INFORMATION_CHANGE',
            16 => 'PRODUCT_CREATION',
            17 => 'SHOPPABLE_CONTENT_POSTING',
            18 => 'PRODUCT_CATEGORY_CHANGE',
            19 => 'SIZE_CHART_CHANGE',
            20 => 'CREATOR_DEAUTHORIZATION',
            21 => 'INBOUND_FBT_ORDER_STATUS_CHANGE',
            22 => 'FBT_MERCHANT_ONBOARDING',
            23 => 'GOODS_MATCH',
            24 => 'FBT_INVENTORY_UPDATE',
            25 => 'OPPORTUNITY_MATCHING_STATUS_CHANGE',
            27 => 'INVENTORY_STATUS_CHANGE',
            33 => 'NEW_MESSAGE_LISTENER',
            35 => 'TOKOPEDIA_MIRROR_STATUS_CHANGE',
            36 => 'INVOICE_STATUS_CHANGE',
            37 => 'PRODUCT_AUDIT_STATUS_CHANGE',
            38 => 'STRIKETHROUGH_PRICE_EXPIRED',
            39 => 'ACTIVITY_STATUS_CHANGE',
            42 => 'COMBINED_LISTING_CHANGE',
            46 => 'IMAGE_TRANSLATION_COMPLETED',
            50 => 'SKU_STATUS_CHANGE',
            51 => 'GLOBAL_REPLICATION_STATUS_CHANGE',
            52 => 'GLOBAL_LISTING_METHOD_CHANGE',
            55 => 'VIDEO_PRECHECK_RESULT',
            56 => 'SAMPLE_APPLICATION_STATUS_CHANGE',
            58 => 'FBT_MCF_ORDER_STATUS',
            59 => 'SHOPPABLE_VIDEO_PRECHECK_TASKS_RESULT',
            62 => 'PRODUCT_PACKAGE_RECOMMENDED',
            63 => 'ACTIVITY_CHANGE',
            64 => 'AFTERSALES_REQUEST_STATUS_UPDATE',
            65 => 'RMA_STATUS_UPDATE',
            66 => 'APPEAL_COMPLETED',
            67 => 'REFUND_SUCCESS',
            68 => 'INVENTORY_CHANGED',
            71 => 'INVENTORY_CHANGED_BY_SHOP',
        ];

        $actual = [];

        foreach (EventType::cases() as $case) {
            $actual[$case->value] = $case->name;
        }

        $this->assertSame($expected, $actual);
    }

    public function test_renamed_cases_no_longer_resolve_by_old_name()
    {
        $this->assertNull(EventType::fromCase('INVOICCE_STATUS_CHANGE'));
        $this->assertNull(EventType::fromCase('FBT_SELLER_ONBOARDING'));
    }
}
