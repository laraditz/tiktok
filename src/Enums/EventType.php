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
    case ORDER_RETURN_STATUS_CHANGE = 12;
    case NEW_CONVERSATION = 13;
    case NEW_MESSAGE = 14;
    case PRODUCT_INFORMATION_CHANGE = 15;
    case PRODUCT_CREATION = 16;
    case SHOPPABLE_CONTENT_POSTING = 17;
    case PRODUCT_CATEGORY_CHANGE = 18;
    case SIZE_CHART_CHANGE = 19;
    case CREATOR_DEAUTHORIZATION = 20;
    case INBOUND_FBT_ORDER_STATUS_CHANGE = 21;
    case FBT_SELLER_ONBOARDING = 22;
    case GOODS_MATCH = 23;
    case FBT_INVENTORY_UPDATE = 24;
    case OPPORTUNITY_MATCHING_STATUS_CHANGE = 25;
    case INVENTORY_STATUS_CHANGE = 27;
    case NEW_MESSAGE_LISTENER = 33;
    case TOKOPEDIA_MIRROR_STATUS_CHANGE = 35;
    case INVOICCE_STATUS_CHANGE = 36;
    case PRODUCT_AUDIT_STATUS_CHANGE = 37;
    case STRIKETHROUGH_PRICE_EXPIRED = 38;
    case ACTIVITY_STATUS_CHANGE = 39;

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
