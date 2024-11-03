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

namespace BaksDev\Avito\Support\Api\Messenger\Post\ReadChat;

use BaksDev\Avito\Api\AvitoApi;


final class AvitoReadChatRequest extends AvitoApi
{

    /**
     * Прочитать чат
     *
     * После успешного получения списка сообщений необходимо вызвать
     * этот метод для того, чтобы чат стал прочитанным.
     *
     * @see https://developers.avito.ru/api-catalog/messenger/documentation#operation/chatRead
     *
     * $avitoChat Идентификатор чата
     */
    public function read(string $avitoChat): bool
    {
        /**
         * Выполнять операции запроса ТОЛЬКО в PROD окружении
         */
        if($this->isExecuteEnvironment() === false)
        {
            return true;
        }

        $response = $this->TokenHttpClient()
            ->request(
                'POST',
                sprintf(
                    '/messenger/v1/accounts/%s/chats/%s/read',
                    $this->getUser(),
                    $avitoChat
                )
            );

        if($response->getStatusCode() !== 200)
        {
            $this->logger->critical('avito-support:Ошибка прочтения чата', [__FILE__.':'.__LINE__]);

            return false;
        }

        $content = $response->toArray(false);

        if(empty($content))
        {
            return false;
        }

        return true;
    }
}
