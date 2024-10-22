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

namespace BaksDev\Avito\Support\Api\Messenger\Get\ChatsInfo;

use BaksDev\Avito\Api\AvitoApi;
use DateInterval;
use DomainException;
use Generator;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Получение информации по чатам
 *
 * @see https://developers.avito.ru/api-catalog/messenger/documentation#operation/getChatsV2
 */
final class AvitoGetChatsInfoRequest extends AvitoApi
{
    /** Идентификатор пользователя (номер профиля авито) */
    private int $avitoProfile;

    public function avitoProfile(int $avitoProfile): self
    {
        $this->avitoProfile = $avitoProfile;

        return $this;
    }

    /** $userId - Идентификатор пользователя (клиента) */
    public function findAll(): Generator
    {
        $cache = $this->getCacheInit('avito-support');

        $response = $cache->get(
            sprintf('%s-%s', 'avito-support-info-chats', $this->profile),
            function(ItemInterface $item): ResponseInterface {

                $item->expiresAfter(DateInterval::createFromDateString('1 min'));

                return $this->TokenHttpClient()
                    ->request(
                        'GET',
                        sprintf('/messenger/v2/accounts/%s/chats', $this->avitoProfile),
                        ['query' =>
                            [
                                /** Получение чатов только по объявлениям с указанными item_id */
                                //  'item_ids'      => [],
                                /** При значении true метод возвращает только непрочитанные чаты */
                                'unread_only' => true,
                                /**
                                 * Фильтрация возвращаемых чатов.
                                 * u2i — чаты по объявлениям;
                                 * u2u — чаты между пользователями;
                                 */
                                'chat_types' => ['u2i', 'u2u'],
                                /** Смещение */
                                //   'offset'        => 1,
                                /** Лимит количества отзывов */
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

        foreach($content['chats'] as $item)
        {
            yield new AvitoChatsDTO($item);
        }
    }
}
