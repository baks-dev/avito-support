<?php
/*
 *  Copyright 2026.  Baks.dev <admin@baks.dev>
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

namespace BaksDev\Avito\Support\Messenger\ReplyMessage\AvitoSupportReplyReview;


use BaksDev\Avito\Support\Api\Review\ReplyToReview\AvitoReplyToReviewRequest;
use BaksDev\Avito\Support\Types\ProfileType\TypeProfileAvitoReviewSupport;
use BaksDev\Avito\Type\Id\AvitoTokenUid;
use BaksDev\Support\Entity\Event\SupportEvent;
use BaksDev\Support\Messenger\SupportMessage;
use BaksDev\Support\Repository\SupportCurrentEvent\CurrentSupportEventInterface;
use BaksDev\Support\Type\Status\SupportStatus\Collection\SupportStatusClose;
use BaksDev\Support\UseCase\Admin\New\Invariable\SupportInvariableDTO;
use BaksDev\Support\UseCase\Admin\New\Message\SupportMessageDTO;
use BaksDev\Support\UseCase\Admin\New\SupportDTO;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(priority: 0)]
final readonly class ReplyAvitoReviewHandler
{
    public function __construct(
        private AvitoReplyToReviewRequest $replyToReviewRequest,
        private CurrentSupportEventInterface $currentSupportEvent,
    ) {}

    public function __invoke(SupportMessage $message): void
    {
        /** @var SupportEvent $support */
        $support = $message->getId();

        $supportEvent = $this->currentSupportEvent
            ->forSupport($support)
            ->find();

        if(false === ($supportEvent instanceof SupportEvent))
        {
            return;
        }

        /** @var SupportDTO $SupportDTO */
        $SupportDTO = $supportEvent->getDto(SupportDTO::class);

        /** @var SupportInvariableDTO $SupportInvariableDTO */
        $SupportInvariableDTO = $SupportDTO->getInvariable();

        /** Получаем тип профиля  */
        $TypeProfileUid = $SupportInvariableDTO->getType();

        /** Обрываем, если статус тикета "открытый" */
        if(false === $SupportDTO->getStatus()->equals(SupportStatusClose::class))
        {
            return;
        }

        /** Обрываем, если тип профиля не Авито Review */
        if(false === $TypeProfileUid->equals(TypeProfileAvitoReviewSupport::TYPE))
        {
            return;
        }

        /** Если получено новое сообщение от клиента */
        $ticket = $SupportInvariableDTO->getTicket();

        /**
         * Получаем последнее сообщение
         *
         * @var SupportMessageDTO $message
         */
        $lastSupportMessage = $SupportDTO->getMessages()->last();

        /** Если у сообщения есть внешний ID, то обрываем  */
        if($lastSupportMessage->getExternal() !== null)
        {
            return;
        }

        $AvitoTokenUid = new AvitoTokenUid($SupportDTO->getToken()->getValue());

        /** Отправляем ответ на отзыв */
        $this->replyToReviewRequest
            ->forTokenIdentifier($AvitoTokenUid)
            ->review($ticket)
            ->send($lastSupportMessage->getMessage());
    }
}
