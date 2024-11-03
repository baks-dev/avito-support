<?php

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
        $SupportDTO = new SupportDTO();

        $supportEvent->getDto($SupportDTO);

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
