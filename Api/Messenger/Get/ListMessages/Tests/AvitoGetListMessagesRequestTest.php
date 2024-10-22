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

namespace BaksDev\Avito\Support\Api\Messenger\Get\ListMessages\Tests;

use BaksDev\Avito\Support\Api\Messenger\Get\ListMessages\AvitoGetListMessagesRequest;
use BaksDev\Avito\Support\Api\Messenger\Get\ListMessages\AvitoListMessagesDTO;
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
class AvitoGetListMessagesRequestTest extends KernelTestCase
{
    private static AvitoTokenAuthorization $authorization;

    public static function setUpBeforeClass(): void
    {

        self::$authorization = new AvitoTokenAuthorization(
            new UserProfileUid(),
            $_SERVER['TEST_AVITO_CLIENT'],
            $_SERVER['TEST_AVITO_SECRET'],
        );
    }

    public function testComplete(): void
    {

        /** @var AvitoGetListMessagesRequest $AvitoGetListMessagesRequest */
        $AvitoGetListMessagesRequest = self::getContainer()->get(AvitoGetListMessagesRequest::class);
        $AvitoGetListMessagesRequest->tokenHttpClient(self::$authorization);
        $AvitoGetListMessagesRequest->avitoChat('bdcc5bac2d00345f1cc66fa657813958');
        $AvitoGetListMessagesRequest->avitoProfile($_SERVER['TEST_AVITO_PROFILE']);

        $messages = $AvitoGetListMessagesRequest->findAll();


        // dd(iterator_to_array($messages));

        if($messages->valid())
        {
            /** @var AvitoListMessagesDTO $AvitoListMessagesDTO */
            $AvitoListMessagesDTO = $messages->current();

            self::assertNotNull($AvitoListMessagesDTO->getId());
            self::assertIsString($AvitoListMessagesDTO->getId());

            self::assertNotNull($AvitoListMessagesDTO->getAuthorId());
            self::assertIsInt($AvitoListMessagesDTO->getAuthorId());

            self::assertNotNull($AvitoListMessagesDTO->getType());
            self::assertIsString($AvitoListMessagesDTO->getType());

            self::assertNotNull($AvitoListMessagesDTO->getText());
            self::assertIsString($AvitoListMessagesDTO->getText());

            self::assertNotNull($AvitoListMessagesDTO->getText());
            self::assertIsString($AvitoListMessagesDTO->getText());

            self::assertNotNull($AvitoListMessagesDTO->getType());
            self::assertIsString($AvitoListMessagesDTO->getType());

            self::assertNotNull($AvitoListMessagesDTO->isRead());
            self::assertIsBool($AvitoListMessagesDTO->isRead());

            self::assertNotNull($AvitoListMessagesDTO->getCreated());
            self::assertInstanceOf(DateTimeImmutable::class, $AvitoListMessagesDTO->getCreated());

        }
        else
        {
            self::assertFalse($messages->valid());
        }
    }
}
