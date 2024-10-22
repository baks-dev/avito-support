<?php

declare(strict_types=1);

namespace BaksDev\Avito\Support\Messenger\ReadMessage;


use BaksDev\Avito\Orders\Type\ProfileType\TypeProfileFbsAvito;
use BaksDev\Avito\Support\Api\Messenger\Post\ReadChat\AvitoReadChatRequest;
use BaksDev\Support\Entity\Event\SupportEvent;
use BaksDev\Support\Messenger\SupportMessage;
use BaksDev\Support\Type\Status\SupportStatus\Collection\SupportStatusOpen;
use BaksDev\Support\UseCase\Admin\New\Invariable\SupportInvariableDTO;
use BaksDev\Support\UseCase\Admin\New\SupportDTO;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(priority: 0)]
final class AvitoReadMessageHandler
{
    public function __construct(
        private AvitoReadChatRequest $readChatRequest,
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

        /**  Обрываем, если статус тикета "закрытый" */
        if(false === $SupportDTO->getStatus()->equals(SupportStatusOpen::class))
        {
            return;
        }

        /**  Обрываем, если тип профиля не Авито */
        if(false === $TypeProfileUid->equals(TypeProfileFbsAvito::TYPE))
        {
            return;
        }

        /** Получаем  ID тикета */
        $ticket = $SupportInvariableDTO->getTicket();

        /** Если сообщение получено из мессенджера Авито, то помечаем его как "прочитанное" */
        if(str_starts_with('AM-', $ticket))
        {
            $this->readChatRequest
                ->avitoChat($ticket)
                ->read();
        }
    }
}
