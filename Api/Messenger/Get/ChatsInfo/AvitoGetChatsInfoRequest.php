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

namespace BaksDev\Avito\Support\Api\Messenger\Get\ChatsInfo;

use BaksDev\Avito\Api\AvitoApi;
use Generator;
use JsonException;

final class AvitoGetChatsInfoRequest extends AvitoApi
{

    /**
     * Получение информации по чатам
     *
     * @see https://developers.avito.ru/api-catalog/messenger/documentation#operation/getChatsV2
     */
    public function findAll(): Generator|false
    {
        /** Собираем массив и присваиваем в переменную query параметры запроса */
        $query = [

            /** Получение чатов только по объявлениям с указанными item_id */
            //  'item_ids'      => [],

            /** При значении true метод возвращает только непрочитанные чаты */
            'unread_only' => 'true',

            /**
             * Фильтрация возвращаемых чатов.
             * u2i — чаты по объявлениям;
             * u2u — чаты между пользователями;
             */
            'chat_types' => 'u2i,u2u',

            /** Смещение */
            // 'offset' => 1,

            /** Лимит количества отзывов */
            // 'limit' => 10
        ];

        $response = $this->TokenHttpClient()
            ->request(
                'GET',
                sprintf('/messenger/v2/accounts/%s/chats', $this->getUser()),
                ['query' => $query]
            );

        try
        {
            $content = $response->toArray(false);

        }
        catch(JsonException)
        {
            return false;
        }

        if($response->getStatusCode() !== 200)
        {
            $this->logger->critical(
                sprintf('avito-support: Ошибка получения чата %s', $this->getUser()),
                [
                    self::class.':'.__LINE__,
                    $content
                ]);

            return false;
        }

        foreach($content['chats'] as $item)
        {
            yield new AvitoChatsDTO($item);
        }
    }
}
