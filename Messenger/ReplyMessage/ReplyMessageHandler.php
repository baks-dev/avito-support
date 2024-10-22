<?php

declare(strict_types=1);

namespace BaksDev\Avito\Support\Messenger\ReplyMessage;


use BaksDev\Avito\Orders\Type\ProfileType\TypeProfileFbsAvito;
use BaksDev\Avito\Support\Api\Messenger\Post\SendMessage\AvitoSendMessageRequest;
use BaksDev\Avito\Support\Api\Review\ReplyToReview\AvitoReplyToReviewRequest;
use BaksDev\Support\Entity\Event\SupportEvent;
use BaksDev\Support\Messenger\SupportMessage;
use BaksDev\Support\Type\Status\SupportStatus\Collection\SupportStatusClose;
use BaksDev\Support\UseCase\Admin\New\Invariable\SupportInvariableDTO;
use BaksDev\Support\UseCase\Admin\New\Message\SupportMessageDTO;
use BaksDev\Support\UseCase\Admin\New\SupportDTO;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(priority: 0)]
final class ReplyMessageHandler
{
    public function __construct(
        private AvitoSendMessageRequest $messageRequest,
        private AvitoReplyToReviewRequest $replyToReviewRequest
    ) {}

    public function __invoke(SupportMessage $message): void
    {

        /** @var SupportEvent $support */
        $support = $message->getEvent();

        /** @var SupportDTO $SupportDTO */
        $SupportDTO = $support->getDto(SupportDTO::class);

        /** @var SupportInvariableDTO $SupportInvariableDTO */
        $SupportInvariableDTO = $SupportDTO->getInvariable();

        /** Получаем тип профиля  */
        $TypeProfileUid = $SupportInvariableDTO->getType();

        /**  Обрываем, если статус тикета "открытый" */
        if(false === $SupportDTO->getStatus()->equals(SupportStatusClose::class))
        {
            return;
        }

        /**  Обрываем, если тип профиля не Авито */
        if(false === $TypeProfileUid->equals(TypeProfileFbsAvito::TYPE))
        {
            return;
        }

        /**  если получено новое сообщение от клиента */
        $ticket = $SupportInvariableDTO->getTicket();

        /** @var SupportMessageDTO $message */
        $message = $SupportDTO->getMessages()->last();

        /** Если сообщение получено из мессенджера Авито, то отправляем ответ в чат мессенджера */
        if(str_starts_with('AM-', $ticket))
        {
            $this->messageRequest
                ->avitoChat($ticket)
                ->send($message->getMessage());
        }

        /** Если сообщение получено из отзывов Авито, то отправляем ответ в чат с отзывами */
        if(str_starts_with('AR-', $ticket))
        {
            $this->replyToReviewRequest
                ->avitoReview($ticket)
                ->send($message->getMessage());
        }
    }
}
