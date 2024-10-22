<?php

declare(strict_types=1);

namespace BaksDev\Avito\Support\Messenger\NewMessage;

use BaksDev\Avito\Orders\Type\ProfileType\TypeProfileFbsAvito;
use BaksDev\Avito\Support\Api\Messenger\Get\ChatsInfo\AvitoChatsDTO;
use BaksDev\Avito\Support\Api\Messenger\Get\ChatsInfo\AvitoGetChatsInfoRequest;
use BaksDev\Avito\Support\Api\Messenger\Get\ListMessages\AvitoGetListMessagesRequest;
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
final class NewSupportHandler
{
    public function __construct(
        private SupportHandler $supportHandler,
        private AvitoGetChatsInfoRequest $getChatsInfoRequest,
        private AvitoGetListMessagesRequest $messagesRequest
    ) {}


    public function __invoke(NewSupportMessage $message): void
    {


        $chats = $this->getChatsInfoRequest
            ->profile($message->getProfile())
            ->findAll();

        /** @var AvitoChatsDTO $chat */
        foreach($chats as $chat)
        {

            $users = $chat->getUsers();

            $listMessages = $this->messagesRequest
                ->avitoChat($chat->getId())
                ->profile($message->getProfile())
                ->findAll();

            foreach($listMessages as $listMessage)
            {
                if(!$listMessage->isRead() && $listMessage->getType() === 'text')
                {

                    /** Выбираем юзера чата из коллекции, сравнивая их ID с ID автора сообщения */
                    $user = $users->filter(fn($u) => $u->getId() === $listMessage->getAuthorId());

                    /** Заполняем ДТО модуля Support */

                    /** SupportDTO */
                    $SupportDTO = new SupportDTO();

                    /** Присваиваем приоритет сообщения  */
                    $SupportDTO->setPriority(new SupportPriority(SupportPriorityLow::PARAM));

                    /** Присваиваем статус "Открытый", так как сообщение еще не прочитано   */
                    $SupportDTO->setStatus(new SupportStatus(SupportStatusOpen::PARAM));


                    /** SupportInvariableDTO */
                    $SupportInvariableDTO = new SupportInvariableDTO();

                    $SupportInvariableDTO->setProfile($message->getProfile());

                    /** Присваиваем идентификатор профиля avito TypeProfileFbsAvito::TYPE  */
                    $SupportInvariableDTO->setType(new TypeProfileUid(TypeProfileFbsAvito::TYPE));

                    /** Присваиваем Id тикета, добавляя префикс AM (avito messenger)  */
                    $SupportInvariableDTO->setTicket(sprintf('AM-%s', $chat->getId()));

                    /** Присваиваем тему сообщения */
                    $SupportInvariableDTO->setTitle($chat->getTitle());


                    /** SupportMessageDTO */
                    $SupportMessageDTO = new SupportMessageDTO();

                    /** Присваиваем имя пользователя */
                    $SupportMessageDTO->setName($user->current()->getName());

                    /** Присваиваем текст сообщения */
                    $SupportMessageDTO->setMessage($listMessage->getText());


                    /** Сохраняем данные в Support */
                    $SupportDTO->addMessage($SupportMessageDTO);

                    $SupportDTO->setInvariable($SupportInvariableDTO);

                    $this->supportHandler->handle($SupportDTO);
                }
            }
        }
    }
}
