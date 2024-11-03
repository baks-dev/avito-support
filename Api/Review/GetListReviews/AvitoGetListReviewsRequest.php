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

namespace BaksDev\Avito\Support\Api\Review\GetListReviews;

use BaksDev\Avito\Api\AvitoApi;
use Generator;


final class AvitoGetListReviewsRequest extends AvitoApi
{
    /**
     * Получение списка активных отзывов на пользователя с пагинацией
     *
     * @see https://developers.avito.ru/api-catalog/ratings/documentation#operation/getReviewsV1
     */
    public function findAll(): Generator
    {
        /** Собираем массив и присваиваем в переменную query параметры запроса */
        $query = [
            /** Смещение */
            'offset' => 1,

            /** Лимит количества отзывов */
            'limit' => 50
        ];

        $response = $this->TokenHttpClient()
            ->request(
                'GET',
                '/ratings/v1/reviews',
                ['query' => $query]
            );

        $content = $response->toArray(false);

        if($response->getStatusCode() !== 200)
        {
            $this->logger->critical('Ошибка получения отзывов', [self::class.':'.__LINE__, $content]);

            return false;
        }

        foreach($content['reviews'] as $item)
        {
            yield new AvitoReviewDTO($item);
        }
    }
}
