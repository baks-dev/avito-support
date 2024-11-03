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

use DateTimeImmutable;

final  class AvitoReviewDTO
{

    /** ID отзыва */
    private int $id;

    /** Имя отправителя */
    private string $sender;

    /** Ответ на отзыв */
    private array|false $answer;

    /** Заголовок объявления */
    private string $title;

    /** Можно ли оставить ответ на отзыв */
    private bool $canAnswer;

    /** Текст отзыва */
    private string $text;

    private DateTimeImmutable $created;


    public function __construct(array $data)
    {
        $this->id = $data['id'];
        $this->sender = $data['sender']['name'];
        $this->answer = !empty($data['answer']) ? $data['answer'] : false;
        $this->title = $data['item']['title'];
        $this->canAnswer = $data['canAnswer'];
        $this->created = (new DateTimeImmutable())->setTimestamp($data['createdAt']);
        $this->text = $this->text($data['text'] ?? '', $data['images'] ?? []);
    }

    private function text(string $text, array $images = []): string
    {
        $result = [];

        if(!empty($images))
        {
            foreach($images as $image)
            {
                foreach($image['sizes'] as $size)
                {
                    $result[] = sprintf(
                        '<img src="%s" title="%s"/>', $size['url'], $image['number']
                    );
                }
            }
        }


        return !empty($result) ? $text.' '.implode(' ', $result) : trim($text);
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getSender(): string
    {
        return $this->sender;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function getCreated(): DateTimeImmutable
    {
        return $this->created;
    }

    public function getAnswer(): false|array
    {
        return $this->answer;
    }

    public function isCanAnswer(): bool
    {
        return $this->canAnswer;
    }

}
