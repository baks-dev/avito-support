<?php
/*
 *  Copyright 2025.  Baks.dev <admin@baks.dev>
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

namespace BaksDev\Avito\Support\Api\Review\ReplyToReview\Tests;

use BaksDev\Avito\Support\Api\Review\ReplyToReview\AvitoReplyToReviewRequest;
use BaksDev\Avito\Type\Authorization\AvitoTokenAuthorization;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use PHPUnit\Framework\Attributes\DependsOnClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
#[Group('avito-support')]
class AvitoReplyToReviewRequestTest extends KernelTestCase
{
    private static AvitoTokenAuthorization $authorization;

    public static function setUpBeforeClass(): void
    {
        self::$authorization = new AvitoTokenAuthorization(
            profile: new UserProfileUid(),
            client: $_SERVER['TEST_AVITO_CLIENT'],
            secret: $_SERVER['TEST_AVITO_SECRET'],
            user: $_SERVER['TEST_AVITO_USER'],
            percent: $_SERVER['TEST_AVITO_PERCENT'] ?? '0',
        );
    }

    public function testComplete(): void
    {
        /**
         * @note Для ручного тестирования закомментировать условие isExecuteEnvironment только PROD
         * @var AvitoReplyToReviewRequest $AvitoReplyToReviewRequest
         */
        $AvitoReplyToReviewRequest = self::getContainer()->get(AvitoReplyToReviewRequest::class);
        $AvitoReplyToReviewRequest->tokenHttpClient(self::$authorization);

        $reviews = $AvitoReplyToReviewRequest
            ->profile(self::$authorization->getProfile())
            ->review('23442221')
            ->send('New Test Message');

        self::assertTrue($reviews);
    }
}
