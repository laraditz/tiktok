<?php

namespace Laraditz\TikTok\Enums;

enum EventType: int
{
    case ORDER_STATUS_CHANGE = 1;
    case REVERSE_STATUS_UPDATE = 2; // legacy
    case RECIPIENT_ADDRESS_UPDATE = 3;
    case PACKAGE_UPDATE = 4;
    case PRODUCT_STATUS_CHANGE = 5;
    case SELLER_DEAUTHORIZATION = 6;
    case AUTH_EXPIRE = 7;
    case CANCELLATION_STATUS_CHANGE = 11;
    case RETURN_STATUS_CHANGE = 12;
    case NEW_CONVERSATION = 13;
    case NEW_MESSAGE = 14;
    case PRODUCT_INFORMATION_CHANGE = 15;
    case PRODUCT_CREATION = 16;
    case SHOPPABLE_CONTENT_POSTING = 17;
    case PRODUCT_CATEGORY_CHANGE = 18;
    case SIZE_CHART_CHANGE = 19;
    case CREATOR_DEAUTHORIZATION = 20;
    case INBOUND_FBT_ORDER_STATUS_CHANGE = 21;
    case FBT_MERCHANT_ONBOARDING = 22;
    case GOODS_MATCH = 23;
    case FBT_INVENTORY_UPDATE = 24;
    case OPPORTUNITY_MATCHING_STATUS_CHANGE = 25;
    case INVENTORY_STATUS_CHANGE = 27;
    case NEW_MESSAGE_LISTENER = 33;
    case TOKOPEDIA_MIRROR_STATUS_CHANGE = 35;
    case INVOICE_STATUS_CHANGE = 36;
    case PRODUCT_AUDIT_STATUS_CHANGE = 37;
    case STRIKETHROUGH_PRICE_EXPIRED = 38;
    case ACTIVITY_STATUS_CHANGE = 39;
    case COMBINED_LISTING_CHANGE = 42;
    case IMAGE_TRANSLATION_COMPLETED = 46;
    case SKU_STATUS_CHANGE = 50;
    case GLOBAL_REPLICATION_STATUS_CHANGE = 51;
    case GLOBAL_LISTING_METHOD_CHANGE = 52;
    case VIDEO_PRECHECK_RESULT = 55;
    case SAMPLE_APPLICATION_STATUS_CHANGE = 56;
    case FBT_MCF_ORDER_STATUS = 58;
    case SHOPPABLE_VIDEO_PRECHECK_TASKS_RESULT = 59;
    case PRODUCT_PACKAGE_RECOMMENDED = 62;
    case ACTIVITY_CHANGE = 63;
    case AFTERSALES_REQUEST_STATUS_UPDATE = 64;
    case RMA_STATUS_UPDATE = 65;
    case APPEAL_COMPLETED = 66;
    case REFUND_SUCCESS = 67;
    case INVENTORY_CHANGED = 68;
    case INVENTORY_CHANGED_BY_SHOP = 71;

    public static function fromCase($case)
    {
        try {
            return (new \ReflectionEnum(self::class))->getCase($case)->getValue();
        } catch (\Throwable $th) {
            //throw $th;
            return null;
        }

    }

}
