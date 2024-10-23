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

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;

final readonly class AvitoChatsDTO
{
    /** ID */
    private string $id;

    /** Title */
    private string $title;

    /** Type (item) */
    private string $type;

    /** Users */
    private ArrayCollection $users;

    /** Created */
    private DateTimeImmutable $created;


    public function __construct(array $data)
    {
        $this->id = $data['id'];
        $this->type = $data['context']['type'];
        $this->title = $data['context']['value']['title'];
        $this->created = (new DateTimeImmutable())->setTimestamp($data['created']);

        $this->users = new ArrayCollection();

        foreach($data['users'] as $user)
        {
            $this->users->add(
                new AvitoChatsUsersDTO(
                    $user['id'],
                    $user['name'],
                )
            );
        }
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getUsers(): ArrayCollection
    {
        return $this->users;
    }

    public function getCreated(): DateTimeImmutable
    {
        return $this->created;
    }
}
