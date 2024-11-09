<?php
/*
 *  Copyright 2024.  Baks.dev <admin@baks.dev>
 *
 *  Permission is hereby granted, free of charge, to any person obtaining a copy
 *  of this software and associated documentation files (the "Software"), to deal
 *  in the Software without restriction, including without limitation the rights
 *  to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 *  copies of the Software, and to permit persons to whom the Software is furnished
 *  to do so, subject to the following conditions:
 *
 *  The above copyright notice and this permission notice shall be included in all
 *  copies or substantial portions of the Software.
 *
 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *  FITNESS FOR A PARTICULAR PURPOSE AND NON INFRINGEMENT. IN NO EVENT SHALL THE
 *  AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 *  LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 *  OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 *  THE SOFTWARE.
 */

declare(strict_types=1);

namespace BaksDev\Avito\Support\Api\Review\GetListReviews\Tests;

use BaksDev\Avito\Support\Api\Review\GetListReviews\AvitoGetListReviewsRequest;
use BaksDev\Avito\Support\Api\Review\GetListReviews\AvitoReviewDTO;
use BaksDev\Avito\Type\Authorization\AvitoTokenAuthorization;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;

/**
 * @group avito-support
 *
 */
#[When(env: 'test')]
class AvitoGetListReviewsRequestTest extends KernelTestCase
{
    private static AvitoTokenAuthorization $authorization;

    public static function setUpBeforeClass(): void
    {
        self::$authorization = new AvitoTokenAuthorization(
            new UserProfileUid(UserProfileUid::TEST),
            $_SERVER['TEST_AVITO_CLIENT'],
            $_SERVER['TEST_AVITO_SECRET'],
            $_SERVER['TEST_AVITO_USER']
        );
    }

    public function testComplete(): void
    {

        /** @var AvitoGetListReviewsRequest $AvitoGetListReviewsRequest */
        $AvitoGetListReviewsRequest = self::getContainer()->get(AvitoGetListReviewsRequest::class);
        $AvitoGetListReviewsRequest->tokenHttpClient(self::$authorization);

        $reviews = $AvitoGetListReviewsRequest->findAll();

        //         dd(iterator_to_array($reviews));

        if($reviews->valid())
        {
            /** @var AvitoReviewDTO $AvitoReviewDTO */
            $AvitoReviewDTO = $reviews->current();

            self::assertNotNull($AvitoReviewDTO->getId());
            self::assertIsInt($AvitoReviewDTO->getId());

            self::assertNotNull($AvitoReviewDTO->getText());
            self::assertIsString($AvitoReviewDTO->getText());

            self::assertNotNull($AvitoReviewDTO->getCreated());
            self::assertInstanceOf(
                DateTimeImmutable::class,
                $AvitoReviewDTO->getCreated()
            );

            self::assertNotNull($AvitoReviewDTO->getSender());
            self::assertIsString($AvitoReviewDTO->getSender());

            self::assertNotNull($AvitoReviewDTO->getAnswer());
            self::assertIsBool($AvitoReviewDTO->getAnswer());

        }
        else
        {
            self::assertFalse($reviews->valid());
        }
    }
}
