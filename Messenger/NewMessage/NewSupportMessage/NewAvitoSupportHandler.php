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

namespace BaksDev\Avito\Support\Messenger\NewMessage\NewSupportMessage;

use BaksDev\Avito\Support\Api\Messenger\Get\ChatsInfo\AvitoChatsDTO;
use BaksDev\Avito\Support\Api\Messenger\Get\ChatsInfo\AvitoGetChatsInfoRequest;
use BaksDev\Avito\Support\Api\Messenger\Get\ListMessages\AvitoGetListMessagesRequest;
use BaksDev\Avito\Support\Types\ProfileType\TypeProfileAvitoMessageSupport;
use BaksDev\Support\Entity\Event\SupportEvent;
use BaksDev\Support\Entity\Support;
use BaksDev\Support\Repository\FindExistMessage\FindExistExternalMessageByIdInterface;
use BaksDev\Support\Repository\SupportCurrentEventByTicket\CurrentSupportEventByTicketInterface;
use BaksDev\Support\Type\Priority\SupportPriority;
use BaksDev\Support\Type\Priority\SupportPriority\Collection\SupportPriorityHeight;
use BaksDev\Support\Type\Status\SupportStatus;
use BaksDev\Support\Type\Status\SupportStatus\Collection\SupportStatusOpen;
use BaksDev\Support\UseCase\Admin\New\Invariable\SupportInvariableDTO;
use BaksDev\Support\UseCase\Admin\New\Message\SupportMessageDTO;
use BaksDev\Support\UseCase\Admin\New\SupportDTO;
use BaksDev\Support\UseCase\Admin\New\SupportHandler;
use BaksDev\Users\Profile\TypeProfile\Type\Id\TypeProfileUid;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(priority: 0)]
final readonly class NewAvitoSupportHandler
{
    public function __construct(
        #[Target('avitoSupportLogger')] private LoggerInterface $logger,
        private SupportHandler $supportHandler,
        private AvitoGetChatsInfoRequest $getChatsInfoRequest,
        private AvitoGetListMessagesRequest $messagesRequest,
        private CurrentSupportEventByTicketInterface $currentSupportEventByTicket,
        private FindExistExternalMessageByIdInterface $findExistMessage,
    ) {}

    /**
     * Метод добавляет новые чаты, добавляет новые сообщения
     */
    public function __invoke(NewAvitoSupportMessage $message): void
    {
        /** Получаем все непрочитанные чаты */
        $chats = $this->getChatsInfoRequest
            ->profile($message->getProfile())
            ->findAll();

        if(false === $chats || false === $chats->valid())
        {
            return;
        }

        /** Все известные типы сообщений из реквеста */
        $messageTypes = ['text', 'call', 'image', 'item', 'link', 'location', 'voice', 'system', 'file'];

        /** @var AvitoChatsDTO $chat */
        foreach($chats as $chat)
        {

            /** Получаем массив с юзерами из чата */
            $users = $chat->getUsers();

            /** Получаем ID чата */
            $ticketId = $chat->getId();

            /** Если такой тикет уже существует в БД, то присваиваем в переменную  $supportEvent */
            $supportEvent = $this->currentSupportEventByTicket
                ->forTicket($ticketId)
                ->find();

            /** Получаем сообщения чата  */
            $listMessages = $this->messagesRequest
                ->profile($message->getProfile())
                ->findAll($chat->getId());

            if(!$listMessages->valid())
            {
                continue;
            }


            $SupportDTO = ($supportEvent instanceof SupportEvent)
                ? $supportEvent->getDto(SupportDTO::class)
                : new SupportDTO();

            /** Присваиваем значения по умолчанию */
            if(false === $supportEvent)
            {
                /** Присваиваем приоритет сообщения Высокий «Height», так как это сообщение от пользователя */
                $SupportDTO->setPriority(new SupportPriority(SupportPriorityHeight::class));

                /** SupportInvariableDTO */
                $SupportInvariableDTO = new SupportInvariableDTO();

                $SupportInvariableDTO
                    ->setProfile($message->getProfile()) // Профиль
                    ->setType(new TypeProfileUid(TypeProfileAvitoMessageSupport::TYPE)) // TypeProfileAvitoMessageSupport::TYPE
                    ->setTicket($ticketId)               //  Id тикета
                    ->setTitle($chat->getTitle());       // Тема сообщения

                /** Сохраняем данные SupportInvariableDTO в Support */
                $SupportDTO->setInvariable($SupportInvariableDTO);

                /** Присваиваем токен для последующего поиска */
                $SupportDTO->getToken()->setValue($message->getProfile());

            }

            /** Присваиваем статус "Открытый", так как сообщение еще не прочитано   */
            $SupportDTO->setStatus(new SupportStatus(SupportStatusOpen::class));

            $isHandle = false;

            foreach($listMessages as $listMessage)
            {
                /** Если такое сообщение уже есть в БД, то пропускаем */
                $messageExist = $this->findExistMessage
                    ->external($listMessage->getExternalId())
                    ->exist();

                if($messageExist)
                {
                    continue;
                }

                /** Если тип сообщения "system", то пропускаем */
                if($listMessage->getType() === 'system' || $listMessage->getType() === 'deleted')
                {
                    continue;
                }

                if(!in_array($listMessage->getType(), $messageTypes))
                {
                    $this->logger->critical(
                        sprintf(
                            'avito-support: Неизвестный тип сообщения - %s',
                            $listMessage->getType(),
                        ),
                        [self::class.':'.__LINE__, $listMessage],
                    );
                }

                /**
                 * Если есть автор сообщения, выбираем юзера чата из коллекции,
                 * сравнивая их ID с ID автора сообщения
                 */
                if($listMessage->getAuthorId())
                {
                    $user = $users->filter(fn($u) => $u->getId() === $listMessage->getAuthorId());
                }

                /** Имя отправителя сообщения */
                $name = !empty($user) ? $user->current()->getName() : 'Без имени';

                /** Текст сообщения */
                $text = $listMessage->getText() ?? 'Сообщение доступно в личном кабинете Avito';


                $SupportMessageDTO = new SupportMessageDTO();

                $SupportMessageDTO
                    ->setExternal($listMessage->getExternalId())    // Внешний (Авито) id сообщения
                    ->setName($name)                                // Имя отправителя сообщения
                    ->setMessage($text)                             // Текст сообщения
                    ->setDate($listMessage->getCreated())           // Дата сообщения
                ;

                /** Если это сообщение изначально наше, то сохраняем с флагом true */
                $listMessage->getDirection() === 'out' ?
                    $SupportMessageDTO->setOutMessage() :
                    $SupportMessageDTO->setInMessage();

                /** Сохраняем данные SupportMessageDTO в Support */
                $isAddMessage = $SupportDTO->addMessage($SupportMessageDTO);

                if(false === $isHandle && true === $isAddMessage)
                {
                    $isHandle = true;
                }
            }

            if($isHandle)
            {
                /** Сохраняем в БД */
                $handle = $this->supportHandler->handle($SupportDTO);

                if(false === ($handle instanceof Support))
                {
                    $this->logger->critical(
                        sprintf('avito-support: Ошибка %s при обновлении чата', $handle),
                        [self::class.':'.__LINE__],
                    );
                }
            }
        }
    }
}
