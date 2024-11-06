<?php

declare(strict_types=1);

namespace BaksDev\Avito\Support\Messenger\ReplyMessage\AvitoSupportReplyReview;


use BaksDev\Avito\Support\Api\Review\ReplyToReview\AvitoReplyToReviewRequest;
use BaksDev\Avito\Support\Types\ProfileType\TypeProfileAvitoReviewSupport;
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
final class ReplyAvitoReviewHandler
{
    private LoggerInterface $logger;

    public function __construct(
        private AvitoReplyToReviewRequest $replyToReviewRequest,
        private CurrentSupportEventInterface $currentSupportEvent,
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

        /** Отправляем ответ на отзыв */
        $this->replyToReviewRequest
            ->profile($SupportInvariableDTO->getProfile())
            ->message($lastSupportMessage->getMessage())
            ->send($ticket);
    }
}
