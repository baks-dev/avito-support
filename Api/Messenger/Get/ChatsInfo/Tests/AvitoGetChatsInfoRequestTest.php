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

namespace BaksDev\Avito\Support\Api\Messenger\Get\ChatsInfo\Tests;

use BaksDev\Avito\Support\Api\Messenger\Get\ChatsInfo\AvitoChatsDTO;
use BaksDev\Avito\Support\Api\Messenger\Get\ChatsInfo\AvitoChatsUsersDTO;
use BaksDev\Avito\Support\Api\Messenger\Get\ChatsInfo\AvitoGetChatsInfoRequest;
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
class AvitoGetChatsInfoRequestTest extends KernelTestCase
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

        /** @var AvitoGetChatsInfoRequest $AvitoGetChatsInfoRequest */
        $AvitoGetChatsInfoRequest = self::getContainer()->get(AvitoGetChatsInfoRequest::class);
        $AvitoGetChatsInfoRequest->tokenHttpClient(self::$authorization);
        $AvitoGetChatsInfoRequest->avitoProfile($_SERVER['TEST_AVITO_PROFILE']);

        $chatsInfo = $AvitoGetChatsInfoRequest->findAll();


        // dd(iterator_to_array($chatsInfo));

        if($chatsInfo->valid())
        {
            /** @var AvitoChatsDTO $AvitoChatsDTO */
            $AvitoChatsDTO = $chatsInfo->current();

            self::assertNotNull($AvitoChatsDTO->getId());
            self::assertIsString($AvitoChatsDTO->getId());

            self::assertNotNull($AvitoChatsDTO->getType());
            self::assertIsString($AvitoChatsDTO->getType());

            self::assertNotNull($AvitoChatsDTO->getTitle());
            self::assertIsString($AvitoChatsDTO->getTitle());

            self::assertNotNull($AvitoChatsDTO->getCreated());
            self::assertInstanceOf(
                DateTimeImmutable::class,
                $AvitoChatsDTO->getCreated()
            );

            if($AvitoChatsDTO->getUsers())
            {
                /** @var AvitoChatsUsersDTO $user */
                $AvitoChatsContextDTO = $AvitoChatsDTO->getUsers();

                $user = $AvitoChatsContextDTO->current();

                self::assertNotNull($user->getId());
                self::assertIsInt($user->getId());

                self::assertNotNull($user->getName());
                self::assertIsInt($user->getName());
            }
        }
        else
        {
            self::assertFalse($chatsInfo->valid());
        }
    }
}
