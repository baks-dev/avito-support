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

use DateTimeImmutable;

final class AvitoListMessagesDTO
{

    /** ID */
    private string $id;

    /** ID автора */
    private int $authorId;

    /** Текст сообщения */
    private string $text;

    /** Прочитано ли сообщение */
    private bool $isRead;

    /** Enum: "text" "image" "link" "item" "location" "call" "deleted" "voice" */
    private string $type;

    private DateTimeImmutable $created;


    public function __construct(array $data)
    {
        $this->id = $data['id'];
        $this->authorId = $data['author_id'];
        $this->text = $data['content']['text'];
        $this->isRead = $data['is_read'];
        $this->type = $data['type'];
        $this->created = (new DateTimeImmutable())->setTimestamp($data['created']);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getAuthorId(): int
    {
        return $this->authorId;
    }

    public function getText(): string
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

    public function getCreated(): DateTimeImmutable
    {
        return $this->created;
    }
}
