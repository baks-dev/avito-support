<?php

declare(strict_types=1);

namespace BaksDev\Avito\Support\Messenger\NewReview;

use BaksDev\Avito\Orders\Type\ProfileType\TypeProfileFbsAvito;
use BaksDev\Avito\Support\Api\Review\GetListReviews\AvitoGetListReviewsRequest;
use BaksDev\Avito\Support\Api\Review\GetListReviews\AvitoReviewDTO;
use BaksDev\Support\Type\Priority\SupportPriority;
use BaksDev\Support\Type\Priority\SupportPriority\Collection\SupportPriorityLow;
use BaksDev\Support\Type\Status\SupportStatus;
use BaksDev\Support\Type\Status\SupportStatus\Collection\SupportStatusOpen;
use BaksDev\Support\UseCase\Admin\New\Invariable\SupportInvariableDTO;
use BaksDev\Support\UseCase\Admin\New\Message\SupportMessageDTO;
use BaksDev\Support\UseCase\Admin\New\SupportDTO;
use BaksDev\Support\UseCase\Admin\New\SupportHandler;
use BaksDev\Users\Profile\TypeProfile\Type\Id\TypeProfileUid;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(priority: 0)]
final class NewSupportReviewHandler
{
    public function __construct(
        private SupportHandler $supportHandler,
        private AvitoGetListReviewsRequest $avitoGetListReviewsRequest
    ) {}


    public function __invoke(NewSupportReviewMessage $message): void
    {


        $chats = $this->avitoGetListReviewsRequest
            ->profile($message->getProfile())
            ->findAll();

        /** @var AvitoReviewDTO $chat */
        foreach($chats as $chat)
        {

            if(false === $chat->isCanAnswer())
            {
                continue;
            }

            /** Заполняем ДТО модуля Support */

            /** SupportDTO */
            $SupportDTO = new SupportDTO();

            /** Присваиваем приоритет сообщения "low" */
            $SupportDTO->setPriority(new SupportPriority(SupportPriorityLow::PARAM));

            /** Присваиваем статус "Открытый", так как сообщение еще не прочитано   */
            $SupportDTO->setStatus(new SupportStatus(SupportStatusOpen::PARAM));


            /** SupportInvariableDTO */
            $SupportInvariableDTO = new SupportInvariableDTO();

            $SupportInvariableDTO->setProfile($message->getProfile());

            /** Присваиваем идентификатор профиля avito TypeProfileFbsAvito::TYPE  */
            $SupportInvariableDTO->setType(new TypeProfileUid(TypeProfileFbsAvito::TYPE));

            /** Присваиваем Id тикета, добавляя префикс AM (avito messenger)  */
            $SupportInvariableDTO->setTicket(sprintf('AR-%s', $chat->getId()));

            /** Присваиваем тему сообщения */
            $SupportInvariableDTO->setTitle($chat->getTitle());


            /** SupportMessageDTO */
            $SupportMessageDTO = new SupportMessageDTO();

            /** Присваиваем имя пользователя */
            $SupportMessageDTO->setName($chat->getSender());

            /** Присваиваем текст сообщения */
            $SupportMessageDTO->setMessage($chat->getText());


            /** Сохраняем данные в Support */
            $SupportDTO->addMessage($SupportMessageDTO);

            $SupportDTO->setInvariable($SupportInvariableDTO);

            $this->supportHandler->handle($SupportDTO);
        }
    }
}
