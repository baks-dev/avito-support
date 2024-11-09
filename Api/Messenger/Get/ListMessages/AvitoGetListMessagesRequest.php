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
use Generator;


final class AvitoGetListMessagesRequest extends AvitoApi
{

    /**
     * Получение списка сообщений V3. Не помечает чат прочитанным.
     * После успешного получения списка сообщений необходимо вызвать метод,
     * который сделает сообщения прочитанными.
     *
     * @see https://developers.avito.ru/api-catalog/messenger/documentation#operation/getMessagesV3
     *
     * $avitoChat Идентификатор чата
     */
    public function findAll(string $avitoChat): Generator|false
    {

        /** Собираем массив и присваиваем в переменную query параметры запроса */
        $query = [

            /** Смещение */
            //   'offset' => 1,

            /** Лимит количества сообщений */
            //  'limit' => 50
        ];

        $response = $this->TokenHttpClient()
            ->request(
                'GET',
                sprintf(
                    '/messenger/v3/accounts/%s/chats/%s/messages/',
                    $this->getUser(),
                    $avitoChat
                ),
                ['query' => $query]
            );


        // Response body is empty
        if($response->getStatusCode() !== 200)
        {
            $this->logger->critical(
                sprintf('avito-support: Ошибка получения сообщений %s', $avitoChat),
                [self::class.':'.__LINE__]
            );

            return false;
        }

        $content = $response->toArray(false);

        foreach(current($content) as $item)
        {
            yield new AvitoListMessagesDTO($item);
        }
    }
}
