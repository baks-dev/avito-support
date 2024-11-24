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

namespace BaksDev\Avito\Support\Messenger\NewMessage\NewSupportReview;

use BaksDev\Avito\Support\Api\Review\GetListReviews\AvitoGetListReviewsRequest;
use BaksDev\Avito\Support\Api\Review\GetListReviews\AvitoReviewDTO;
use BaksDev\Avito\Support\Types\ProfileType\TypeProfileAvitoReviewSupport;
use BaksDev\Support\Entity\Support;
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
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(priority: 0)]
final readonly class NewAvitoSupportReviewHandler
{
    private LoggerInterface $logger;

    public function __construct(
        private SupportHandler $supportHandler,
        private AvitoGetListReviewsRequest $avitoGetListReviewsRequest,
        private CurrentSupportEventByTicketInterface $currentSupportEventByTicket,
        LoggerInterface $avitoSupportLogger,
    )
    {
        $this->logger = $avitoSupportLogger;
    }


    public function __invoke(NewAvitoSupportReviewMessage $message): void
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
                $supportEvent->getDto($SupportDTO);
            }

            if(false === $supportEvent)
            {
                /** Присваиваем приоритет сообщения Низкий «low» */
                $SupportDTO->setPriority(new SupportPriority(SupportPriorityLow::class));

                $SupportInvariableDTO = new SupportInvariableDTO();

                $SupportInvariableDTO
                    ->setProfile($message->getProfile())
                    ->setType(new TypeProfileUid(TypeProfileAvitoReviewSupport::TYPE))
                    ->setTicket($review->getId()) // Id тикета
                    ->setTitle($review->getTitle()); // Тема сообщения

                /** Сохраняем данные SupportInvariableDTO в Support */
                $SupportDTO->setInvariable($SupportInvariableDTO);
            }

            /** Присваиваем статус "Открытый", так как сообщение еще не прочитано   */
            $SupportDTO->setStatus(new SupportStatus(SupportStatusOpen::class));


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
            $handle = $this->supportHandler->handle($SupportDTO);

            if($handle instanceof Support)
            {
                $this->logger->critical(
                    sprintf('avito-support: Ошибка %s при обновлении чата', $handle),
                    [self::class.':'.__LINE__]
                );
            }

        }
    }
}
