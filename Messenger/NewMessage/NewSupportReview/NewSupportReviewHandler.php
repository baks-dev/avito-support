<?php

declare(strict_types=1);

namespace BaksDev\Avito\Support\Messenger\NewMessage\NewSupportReview;

use BaksDev\Avito\Support\Api\Review\GetListReviews\AvitoGetListReviewsRequest;
use BaksDev\Avito\Support\Api\Review\GetListReviews\AvitoReviewDTO;
use BaksDev\Avito\Support\Types\ProfileType\TypeProfileAvitoReviewSupport;
use BaksDev\Support\Repository\SupportCurrentEventByTicket\CurrentSupportEventByTicketInterface;
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
        private AvitoGetListReviewsRequest $avitoGetListReviewsRequest,
        private CurrentSupportEventByTicketInterface $currentSupportEventByTicket,
    ) {}


    public function __invoke(NewSupportReviewMessage $message): void
    {
        /** Получаем все отзывы */
        $reviews = $this->avitoGetListReviewsRequest
            ->profile($message->getProfile())
            ->findAll();

        if(!$reviews->valid())
        {
            return;
        }

        /** @var AvitoReviewDTO $review */
        foreach($reviews as $review)
        {

            /** Если на отзыв нельзя ответить, пропускаем */
            if(false === $review->isCanAnswer())
            {
                continue;
            }

            /** Если ответ на отзыв уже есть, пропускаем такой отзыв */
            //            if(false !== $review->getAnswer())
            //            {
            //                continue;
            //            }

            /** Получаем ID чата с отзывом  */
            $ticketId = $review->getId();

            /** Если такой чат существует, сохраняем его event в переменную  */
            $supportEvent = $this->currentSupportEventByTicket
                ->forTicket($ticketId)
                ->find();


            /** SupportDTO */
            $SupportDTO = new SupportDTO();

            if($supportEvent)
            {
                $supportEvent->getDto($supportEvent);
            }

            if(false === $supportEvent)
            {
                /** Присваиваем приоритет сообщения "low" */
                $SupportDTO->setPriority(new SupportPriority(SupportPriorityLow::PARAM));

                /** SupportInvariableDTO */
                $SupportInvariableDTO = new SupportInvariableDTO();

                $SupportInvariableDTO->setProfile($message->getProfile());

                $SupportInvariableDTO
                    ->setType(new TypeProfileUid(TypeProfileAvitoReviewSupport::TYPE)) // TypeProfileAvitoReviewSupport::TYPE
                    ->setTicket($review->getId()) // Id тикета
                    ->setTitle($review->getTitle()); // Тема сообщения

                /** Сохраняем данные SupportInvariableDTO в Support */
                $SupportDTO->setInvariable($SupportInvariableDTO);
            }

            /** Присваиваем статус "Открытый", так как сообщение еще не прочитано   */
            $SupportDTO->setStatus(new SupportStatus(SupportStatusOpen::PARAM));


            /** SupportMessageDTO */
            $SupportMessageDTO = new SupportMessageDTO();

            $SupportMessageDTO
                ->setName($review->getSender())     // Имя пользователя
                ->setMessage($review->getText())    // Текст сообщения
                ->setExternal($review->getId())     // Внешний (avito) ID сообщения
                ->setDate($review->getCreated())    // Дата сообщения
                ->setInMessage();                   // Входящее сообщение

            /** Сохраняем данные в Support */
            $SupportDTO->addMessage($SupportMessageDTO);


            /** Сохраняем в БД */
            $this->supportHandler->handle($SupportDTO);
        }
    }
}
