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

namespace BaksDev\Avito\Support\Api\Messenger\Post\SendMessage;

use BaksDev\Avito\Api\AvitoApi;
use InvalidArgumentException;


final class AvitoSendMessageRequest extends AvitoApi
{
    private string|false $message = false;

    private string $type = 'text';

    public function message(string $message): self
    {
        $this->message = $message;
        return $this;
    }

    /**
     * Отправка сообщения в чат
     *
     * @see https://developers.avito.ru/api-catalog/messenger/documentation#operation/postSendMessage
     *
     * $message - Текст сообщения
     */
    public function send(string $avitoChat): bool
    {
        if($this->isExecuteEnvironment() === false)
        {
            $this->logger->critical('Запрос может быть выполнен только в PROD окружении', [self::class.':'.__LINE__]);
            return true;
        }

        if($this->message === false)
        {
            throw new InvalidArgumentException('Invalid Argument $message');
        }

        if(empty($this->message))
        {
            return true;
        }

        /** Собираем в массив и присваиваем в переменную тело запроса */
        $body = [
            'message' => ['text' => $this->message],
            'type' => $this->type
        ];

        $response = $this->TokenHttpClient()
            ->request(
                'POST',
                sprintf('/messenger/v1/accounts/%s/chats/%s/messages', $this->getUser(), $avitoChat),
                [
                    "json" => $body
                ]
            );

        $content = $response->toArray(false);

        if($response->getStatusCode() !== 200)
        {
            $this->logger->critical(
                'avito-support: Ошибка отправки сообщения в чат ',
                [self::class.':'.__LINE__, $body, $content],
            );

            return false;
        }


        if(empty($content))
        {
            return false;
        }

        return true;
    }
}
