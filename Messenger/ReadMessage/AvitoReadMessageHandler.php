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

namespace BaksDev\Avito\Support\Messenger\ReadMessage;


use BaksDev\Avito\Support\Api\Messenger\Post\ReadChat\AvitoReadChatRequest;
use BaksDev\Avito\Support\Types\ProfileType\TypeProfileAvitoMessageSupport;
use BaksDev\Support\Entity\Event\SupportEvent;
use BaksDev\Support\Messenger\SupportMessage;
use BaksDev\Support\Repository\SupportCurrentEvent\CurrentSupportEventInterface;
use BaksDev\Support\Type\Status\SupportStatus\Collection\SupportStatusOpen;
use BaksDev\Support\UseCase\Admin\New\Invariable\SupportInvariableDTO;
use BaksDev\Support\UseCase\Admin\New\SupportDTO;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(priority: 0)]
final class AvitoReadMessageHandler
{
    public function __construct(
        private AvitoReadChatRequest $readChatRequest,
        private CurrentSupportEventInterface $currentSupportEvent,
    ) {}

    /**
     * Помечаем сообщение Авито как "прочитанное"
     */
    public function __invoke(SupportMessage $message): void
    {
        /** @var SupportEvent $support */
        $support = $message->getId();

        $supportEvent = $this->currentSupportEvent
            ->forSupport($support)
            ->find();

        if(false === $supportEvent)
        {
            return;
        }

        /** @var SupportDTO $SupportDTO */
        $SupportDTO = $supportEvent->getDto(SupportDTO::class);

        /** @var SupportInvariableDTO $SupportInvariableDTO */
        $SupportInvariableDTO = $SupportDTO->getInvariable();

        /** Получаем тип профиля  */
        $TypeProfileUid = $SupportInvariableDTO->getType();

        /**  Обрываем, если статус тикета "закрытый" */
        if(false === $SupportDTO->getStatus()->equals(SupportStatusOpen::class))
        {
            return;
        }

        /**  Обрываем, если тип профиля не Авито и сообщение не из мессенджера */
        if(false === $TypeProfileUid->equals(TypeProfileAvitoMessageSupport::TYPE))
        {
            return;
        }

        /** Получаем  ID тикета */
        $ticket = $SupportInvariableDTO->getTicket();

        /** Помечаем сообщение как "прочитанное", полученное из мессенджера Авито */
        $this->readChatRequest
            ->profile($SupportInvariableDTO->getProfile())
            ->read($ticket);
    }
}
