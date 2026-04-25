<?php

declare(strict_types=1);

use App\Contracts\AppCodeResolverInterface;
use App\Contracts\TagClassifierInterface;
use App\DTO\ClassifiedTags;
use App\DTO\OutputRecord;
use App\DTO\RawRecord;
use App\Enums\Contactable;
use App\Enums\FreeProductDownloadStatus;
use App\Enums\InAppPurchaseProductDownloadStatus;
use App\Enums\SubscriptionStatus;
use App\Services\RecordTransformer;
use Psr\Log\LoggerInterface;

beforeEach(function () {
    $this->appCodeResolver = Mockery::mock(AppCodeResolverInterface::class);
    $this->tagClassifier = Mockery::mock(TagClassifierInterface::class);
    $this->logger = Mockery::mock(LoggerInterface::class);

    $this->transformer = new RecordTransformer(
        $this->appCodeResolver,
        $this->tagClassifier,
        $this->logger,
    );
});

afterEach(fn() => Mockery::close());

it('transforms a RawRecord into an OutputRecord', function () {
    $raw = RawRecord::fromArray([
        'app' => 'SFX Collection',
        'deviceToken' => 'ABC123',
        'deviceTokenStatus' => '1',
        'tags' => 'never_subscribed',
    ]);

    $classifiedTags = new ClassifiedTags(
        subscriptionStatus: SubscriptionStatus::NeverSubscribed,
        freeProductDownloadStatus: FreeProductDownloadStatus::Unknown,
        inAppPurchaseProductDownloadStatus: InAppPurchaseProductDownloadStatus::Unknown,
    );

    $this->appCodeResolver->expects('resolve')->with('SFX Collection')->andReturn('sfx-collection');
    $this->tagClassifier->expects('classify')->with('never_subscribed')->andReturn($classifiedTags);
    $this->logger->shouldNotReceive('warning');

    $output = $this->transformer->transform($raw, 1);

    expect($output)->toBeInstanceOf(OutputRecord::class)
        ->and($output->id)->toBe(1)
        ->and($output->appCode)->toBe('sfx-collection')
        ->and($output->deviceId)->toBe('ABC123')
        ->and($output->contactable)->toBe(Contactable::Yes)
        ->and($output->subscriptionStatus)->toBe(SubscriptionStatus::NeverSubscribed);
});

it('maps deviceTokenStatus to Contactable enum', function () {
    $this->appCodeResolver->allows('resolve')->andReturn('sfx-collection');
    $this->tagClassifier->allows('classify')->andReturn(new ClassifiedTags(
        subscriptionStatus: SubscriptionStatus::Unknown,
        freeProductDownloadStatus: FreeProductDownloadStatus::Unknown,
        inAppPurchaseProductDownloadStatus: InAppPurchaseProductDownloadStatus::Unknown,
    ));
    $this->logger->shouldNotReceive('warning');

    $contactable = $this->transformer->transform(
        RawRecord::fromArray(['app' => 'SFX Collection', 'deviceToken' => 'ABC123', 'deviceTokenStatus' => '1', 'tags' => '']),
        1,
    );

    $notContactable = $this->transformer->transform(
        RawRecord::fromArray(['app' => 'SFX Collection', 'deviceToken' => 'ABC123', 'deviceTokenStatus' => '', 'tags' => '']),
        2,
    );

    expect($contactable->contactable)->toBe(Contactable::Yes)
        ->and($notContactable->contactable)->toBe(Contactable::No);
});

it('logs a warning when unrecognized tags are encountered', function () {
    $raw = RawRecord::fromArray([
        'app' => 'SFX Collection',
        'deviceToken' => 'ABC123',
        'deviceTokenStatus' => '',
        'tags' => 'purchased_single_issue_while_active_sub|active_subscriber',
    ]);

    $classifiedTags = new ClassifiedTags(
        subscriptionStatus: SubscriptionStatus::ActiveSubscriber,
        freeProductDownloadStatus: FreeProductDownloadStatus::Unknown,
        inAppPurchaseProductDownloadStatus: InAppPurchaseProductDownloadStatus::Unknown,
        unrecognizedTags: ['purchased_single_issue_while_active_sub'],
    );

    $this->appCodeResolver->expects('resolve')->andReturn('sfx-collection');
    $this->tagClassifier->expects('classify')->andReturn($classifiedTags);
    $this->logger->expects('warning')->with(
        'Unrecognized tags encountered',
        Mockery::on(fn(array $context) => $context['deviceToken'] === 'ABC123'
            && $context['unrecognizedTags'] === ['purchased_single_issue_while_active_sub']),
    );

    $output = $this->transformer->transform($raw, 1);

    expect($output->subscriptionStatus)->toBe(SubscriptionStatus::ActiveSubscriber);
});

it('does not log when all tags are recognized', function () {
    $this->appCodeResolver->expects('resolve')->andReturn('sfx-collection');
    $this->tagClassifier->expects('classify')->andReturn(new ClassifiedTags(
        subscriptionStatus: SubscriptionStatus::NeverSubscribed,
        freeProductDownloadStatus: FreeProductDownloadStatus::Unknown,
        inAppPurchaseProductDownloadStatus: InAppPurchaseProductDownloadStatus::Unknown,
        unrecognizedTags: [],
    ));
    $this->logger->shouldNotReceive('warning');

    $this->transformer->transform(
        RawRecord::fromArray(['app' => 'SFX Collection', 'deviceToken' => 'ABC123', 'deviceTokenStatus' => '1', 'tags' => 'never_subscribed']),
        1,
    );
});

it('passes the id through to the OutputRecord', function () {
    $this->appCodeResolver->expects('resolve')->andReturn('sfx-collection');
    $this->tagClassifier->expects('classify')->andReturn(new ClassifiedTags(
        subscriptionStatus: SubscriptionStatus::Unknown,
        freeProductDownloadStatus: FreeProductDownloadStatus::Unknown,
        inAppPurchaseProductDownloadStatus: InAppPurchaseProductDownloadStatus::Unknown,
    ));
    $this->logger->shouldNotReceive('warning');

    $output = $this->transformer->transform(
        RawRecord::fromArray(['app' => 'SFX Collection', 'deviceToken' => 'ABC123', 'deviceTokenStatus' => '', 'tags' => '']),
        99,
    );

    expect($output->id)->toBe(99);
});
