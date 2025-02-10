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

namespace BaksDev\Avito\Support\Api\Review\ReplyToReview;

use BaksDev\Avito\Api\AvitoApi;
use InvalidArgumentException;


final class AvitoReplyToReviewRequest extends AvitoApi
{
    /**  ID отзыва */
    private int|false $review = false;

    public function review(string|int $review): self
    {
        $this->review = (int) $review;

        return $this;
    }

    private string $message;

    public function setMessage(string $message): self
    {
        $this->message = $message;
        return $this;
    }


    /**
     * Отправка ответа на отзыв
     *
     * @see https://developers.avito.ru/api-catalog/ratings/documentation#operation/createReviewAnswerV1
     *
     * @var $message - Текст ответа на отзыв
     */
    public function send(string $message): bool
    {
        if($this->isExecuteEnvironment() === false)
        {
            $this->logger->critical('Запрос может быть выполнен только в PROD окружении', [self::class.':'.__LINE__]);
            return true;
        }

        if(false === $this->review)
        {
            throw new InvalidArgumentException('Invalid Argument $review');
        }

        /** Собираем в массив и присваиваем в переменную тело запроса */
        $body = [
            'message' => $message,
            'reviewId' => $this->review
        ];

        $response = $this->TokenHttpClient()
            ->request(
                'POST',
                '/ratings/v1/answers',
                ["json" => $body]
            );

        $content = $response->toArray(false);

        if($response->getStatusCode() !== 200)
        {
            $this->logger->critical(
                sprintf('Ошибка отправки ответа на отзыв  %s', $this->review),
                [self::class.':'.__LINE__, $content]
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
