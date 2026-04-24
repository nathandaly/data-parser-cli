<?php

namespace App\Enums;

enum SubscriptionStatus: string
{
    case ActiveSubscriber = 'active_subscriber';

    case ExpiredSubscriber = 'expired_subscriber';

    case NeverSubscribed = 'never_subscribed';

    case Unknown = 'subscription_unknown';

    public static function tryFromTag(string $tag): ?self
    {
        return match ($tag) {
            'active_subscriber' => self::ActiveSubscriber,
            'expired_subscriber' => self::ExpiredSubscriber,
            'never_subscribed' => self::NeverSubscribed,
            default => null,
        };
    }
}
