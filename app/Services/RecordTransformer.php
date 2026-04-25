<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\AppCodeResolverInterface;
use App\Contracts\RecordTransformerInterface;
use App\Contracts\TagClassifierInterface;
use App\DTO\OutputRecord;
use App\DTO\RawRecord;
use App\Enums\Contactable;
use Psr\Log\LoggerInterface;

class RecordTransformer implements RecordTransformerInterface
{
    public function __construct(
        private readonly AppCodeResolverInterface $appCodeResolver,
        private readonly TagClassifierInterface $tagClassifier,
        private readonly LoggerInterface $logger,
    ) {}

    public function transform(RawRecord $record, int $id): OutputRecord
    {
        $appCode = $this->appCodeResolver->resolve($record->app);
        $classifiedTags = $this->tagClassifier->classify($record->tags);

        if ($classifiedTags->unrecognizedTags !== []) {
            $this->logger->warning('Unrecognized tags encountered', [
                'deviceToken' => $record->deviceToken,
                'app' => $record->app,
                'unrecognizedTags' => $classifiedTags->unrecognizedTags,
            ]);
        }

        return OutputRecord::fromArray([
            'id' => $id,
            'appCode' => $appCode,
            'deviceId' => $record->deviceToken,
            'contactable' => Contactable::fromDeviceTokenStatus($record->deviceTokenStatus),
            'subscriptionStatus' => $classifiedTags->subscriptionStatus,
            'freeProductDownloadStatus' => $classifiedTags->freeProductDownloadStatus,
            'inAppPurchaseProductDownloadStatus' => $classifiedTags->inAppPurchaseProductDownloadStatus,
        ]);
    }
}
