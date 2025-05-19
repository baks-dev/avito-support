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

namespace BaksDev\Avito\Support\Api\Messenger\Get\ListMessages;

use DateTimeImmutable;

final class AvitoListMessagesDTO
{

    /** ID */
    private string $externalId;

    /** ID автора */
    private int $authorId;

    /** Текст сообщения */
    private ?string $text = null;

    /** Прочитано ли сообщение */
    private bool $isRead;

    /** Enum: "text" "image" "link" "item" "location" "call" "deleted" "voice" */
    private string $type;

    /** Входящее/Исходящее (Enum: "in" "out") */
    private string $direction;

    private DateTimeImmutable $created;


    public function __construct(array $data)
    {
        $this->externalId = $data['id'];
        $this->authorId = $data['author_id'];
        $this->isRead = $data['isRead'];
        $this->type = $data['type'];
        $this->direction = $data['direction'];
        $this->created = (new DateTimeImmutable())->setTimestamp($data['created']);

        $text = match ($data['type'])
        {
            'call' => $this->call($data['content']['call']),              // голосовой вызов
            'image' => $this->image($data['content']['image']),           // фотография
            'item' => $this->item($data['content']['item']),              // ссылка на объявление
            'link' => $this->link($data['content']['link']),              // ссылка
            'location' => $this->location($data['content']['location']),  // геолокация
            'voice' => $this->voice($data['content']['voice']),           // голосовое сообщение
            default => $data['content']['text'] ?? null,                  // текстовое сообщение
        };

        $this->text = $text;

    }

    /** Уведомление о пропущенном вызове */
    private function call($call): string
    {
        return sprintf('У Вас пропущенный вызов от пользователя ID = %s', $call['target_user_id']);
    }

    /** Сообщение в виде image */
    private function image($image): string
    {
        $small = sprintf('<img src="%s" />', $image['sizes']['32x32']);
        $large = sprintf('<a href="%s" class="ms-3" target="_blank" />Открыть полное фото</a>', $image['sizes']['1280x960']);

        return $small.' '.$large;
    }

    /** Сообщение в виде ссылки на объявление */
    private function item($item): string
    {
        $imageUrl = sprintf('<img src="%s" />', $item['image_url']);
        $title = $item['title'];
        $itemUrl = $item['item_url'];

        return sprintf('<a href="%s" target="_blank" />', $itemUrl).$title.' '.$imageUrl.'<a/>';
    }

    /** Сообщение в виде ссылки на ресурс */
    private function link($link): string
    {
        $url = $link['url'];
        $title = $link['text'];

        return sprintf('<a href="%s" target="_blank" />', $url).$title.'<a/>';
    }

    /** Сообщение в виде ссылки на яндекс карты */
    private function location($location): string
    {
        $lat = $location['lat'];
        $lon = $location['lon'];
        $title = $location['title'];

        $link = sprintf('https://yandex.by/maps/?ll=%s,%s&z=8', $lat, $lon);

        return sprintf('<a href="%s" />', $link).$title.'</a>';
    }

    /** Уведомление о полученном голосовом сообщении */
    private function voice($voice): string
    {
        return sprintf('У Вас получено голосовое сообщение ID = %s', $voice['voice_id']);
    }


    public function getExternalId(): string
    {
        return $this->externalId;
    }

    public function getAuthorId(): int
    {
        return $this->authorId;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function isRead(): bool
    {
        return $this->isRead;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getDirection(): string
    {
        return $this->direction;
    }

    public function getCreated(): DateTimeImmutable
    {
        return $this->created;
    }
}
