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

namespace BaksDev\Avito\Support\Api\Messenger\Get\ListMessages;

use BaksDev\Avito\Api\AvitoApi;
use DateInterval;
use DomainException;
use Generator;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Получение списка сообщений V3. Не помечает чат прочитанным.
 * После успешного получения списка сообщений необходимо вызвать метод,
 * который сделает сообщения прочитанными.
 *
 * @see https://developers.avito.ru/api-catalog/messenger/documentation#operation/getMessagesV3
 */
final class AvitoGetListMessagesRequest extends AvitoApi
{
    /** Идентификатор пользователя (номер профиля авито) */
    private int $avitoProfile;

    /** Идентификатор чата */
    private string $avitoChat;

    public function avitoProfile(int $avitoProfile): self
    {
        $this->avitoProfile = $avitoProfile;

        return $this;
    }

    public function avitoChat(string $avitoChat): self
    {
        $this->avitoChat = $avitoChat;

        return $this;
    }

    public function findAll(): Generator
    {
        $cache = $this->getCacheInit('avito-support');

        $response = $cache->get(
            sprintf('%s-%s-%s', 'avito-support-list-messages', $this->avitoChat, $this->profile),
            function(ItemInterface $item): ResponseInterface {

                $item->expiresAfter(DateInterval::createFromDateString('1 min'));

                return $this->TokenHttpClient()
                    ->request(
                        'GET',
                        sprintf(
                            '/messenger/v3/accounts/%s/chats/%s/messages/',
                            $this->avitoProfile,
                            $this->avitoChat
                        ),
                        ['query' =>
                            [
                                /** Смещение */
                                //   'offset'        => 1,
                                /** Лимит количества сообщений */
                                //  'limit'         => 50
                            ]
                        ]
                    );
            }
        );

        $content = $response->toArray(false);

        if($response->getStatusCode() !== 200)
        {
            $this->logger->critical($content['error']['code'].': '.$content['error']['message'], [__FILE__.':'.__LINE__]);

            throw new DomainException(
                message: 'Ошибка '.self::class,
                code: $response->getStatusCode()
            );
        }

        foreach($content as $item)
        {
            yield new AvitoListMessagesDTO($item);
        }
    }
}
