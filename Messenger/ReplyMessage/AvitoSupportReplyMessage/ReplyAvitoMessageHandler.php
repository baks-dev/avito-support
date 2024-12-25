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

namespace BaksDev\Avito\Support\Messenger\ReplyMessage\AvitoSupportReplyMessage;


use BaksDev\Avito\Support\Api\Messenger\Post\SendMessage\AvitoSendMessageRequest;
use BaksDev\Avito\Support\Types\ProfileType\TypeProfileAvitoMessageSupport;
use BaksDev\Core\Messenger\MessageDelay;
use BaksDev\Core\Messenger\MessageDispatchInterface;
use BaksDev\Support\Entity\Event\SupportEvent;
use BaksDev\Support\Messenger\SupportMessage;
use BaksDev\Support\Repository\SupportCurrentEvent\CurrentSupportEventInterface;
use BaksDev\Support\Type\Status\SupportStatus\Collection\SupportStatusClose;
use BaksDev\Support\UseCase\Admin\New\Invariable\SupportInvariableDTO;
use BaksDev\Support\UseCase\Admin\New\Message\SupportMessageDTO;
use BaksDev\Support\UseCase\Admin\New\SupportDTO;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(priority: 0)]
final class ReplyAvitoMessageHandler
{
    private LoggerInterface $logger;

    public function __construct(
        private AvitoSendMessageRequest $messageRequest,
        private CurrentSupportEventInterface $currentSupportEvent,
        private readonly MessageDispatchInterface $messageDispatcher,
        LoggerInterface $avitoSupportLogger,
    )
    {
        $this->logger = $avitoSupportLogger;
    }

    public function __invoke(SupportMessage $message): void
    {

        /** @var SupportEvent $support */
        $support = $message->getId();

        $this->currentSupportEvent->forSupport($support);

        $supportEvent = $this->currentSupportEvent->find();

        if(false === $supportEvent)
        {
            return;
        }

        /** @var SupportDTO $SupportDTO */
        $SupportDTO = new SupportDTO();

        $supportEvent->getDto($SupportDTO);

        /** @var SupportInvariableDTO $SupportInvariableDTO */
        $SupportInvariableDTO = $SupportDTO->getInvariable();

        /** Получаем тип профиля  */
        $TypeProfileUid = $SupportInvariableDTO->getType();

        /** Обрываем, если статус тикета "открытый" */
        if(false === $SupportDTO->getStatus()->equals(SupportStatusClose::class))
        {
            return;
        }

        /** Обрываем, если тип профиля не Авито Message */
        if(false === $TypeProfileUid->equals(TypeProfileAvitoMessageSupport::TYPE))
        {
            return;
        }

        /**
         * Получаем последнее сообщение
         * @var SupportMessageDTO $lastMessage
         */
        $lastMessage = $SupportDTO->getMessages()->last();

        /** Если у сообщения есть внешний ID, то обрываем  */
        if($lastMessage->getExternal() !== null)
        {
            return;
        }

        $ticket = $SupportInvariableDTO->getTicket();

        /** Отправка сообщения */
        $send = $this->messageRequest
            ->profile($SupportInvariableDTO->getProfile())
            ->message($lastMessage->getMessage())
            ->send($ticket);

        if(false === $send)
        {
            /** Пробуем отправить отправить через минуту */
            $this->messageDispatcher->dispatch(
                message: $message,
                stamps: [new MessageDelay('1 minutes')],
                transport: 'avito-support'
            );
        }
    }
}
