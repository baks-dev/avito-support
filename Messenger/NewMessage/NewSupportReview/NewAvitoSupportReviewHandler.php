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

namespace BaksDev\Avito\Support\Messenger\NewMessage\NewSupportReview;

use BaksDev\Avito\Support\Api\Review\GetListReviews\AvitoGetListReviewsRequest;
use BaksDev\Avito\Support\Api\Review\GetListReviews\AvitoReviewDTO;
use BaksDev\Avito\Support\Types\ProfileType\TypeProfileAvitoReviewSupport;
use BaksDev\Core\Deduplicator\DeduplicatorInterface;
use BaksDev\Support\Entity\Support;
use BaksDev\Support\Repository\ExistTicket\ExistSupportTicketInterface;
use BaksDev\Support\Repository\FindExistTicket\FindExistTicketInterface;
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
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(priority: 0)]
final readonly class NewAvitoSupportReviewHandler
{
    public function __construct(
        #[Target('avitoSupportLogger')] private LoggerInterface $logger,
        private SupportHandler $supportHandler,
        private AvitoGetListReviewsRequest $avitoGetListReviewsRequest,
        private ExistSupportTicketInterface $ExistSupportTicket,
        private DeduplicatorInterface $deduplicator,
    ) {}

    public function __invoke(NewAvitoSupportReviewMessage $message): void
    {

        $isExecuted = $this
            ->deduplicator
            ->namespace('avito-support')
            ->expiresAfter('1 minute')
            ->deduplication([self::class]);

        if(true === $isExecuted->isExecuted())
        {
            return;
        }

        $isExecuted->save();

        /**
         * Получаем все отзывы
         */

        $reviews = $this->avitoGetListReviewsRequest
            ->profile($message->getProfile())
            ->findAll();

        if(!$reviews->valid())
        {
            $isExecuted->delete();
            return;
        }

        /** @var AvitoReviewDTO $review */
        foreach($reviews as $review)
        {
            /** Если на отзыв нельзя ответить (в случае ответа), пропускаем */
            if(false === $review->isCanAnswer())
            {
                continue;
            }

            /** Пропускаем обработанные отзывы */

            $Deduplicator = $this->deduplicator
                ->expiresAfter('1 day')
                ->deduplication([$review->getId(), self::class]);

            if(true === $Deduplicator->isExecuted())
            {
                continue;
            }


            $isExistTicket = $this->ExistSupportTicket->ticket($review->getId())->exist();

            if(true === $isExistTicket)
            {
                $Deduplicator->save();
                continue;
            }

            /**
             * Создаем тикет
             */

            $SupportDTO = new SupportDTO(); // done

            /** Присваиваем токен для последующего поиска */
            $SupportDTO
                ->getToken()
                ->setValue($message->getProfile());

            $SupportDTO
                ->setStatus(new SupportStatus(SupportStatusOpen::class))
                ->setPriority(new SupportPriority(SupportPriorityLow::class));



            /**
             * Создаем неизменные данные
             */

            $SupportInvariableDTO = new SupportInvariableDTO();

            $SupportInvariableDTO
                ->setProfile($message->getProfile())
                ->setType(new TypeProfileUid(TypeProfileAvitoReviewSupport::TYPE))
                ->setTicket($review->getId()) // Id тикета
                ->setTitle($review->getTitle()); // Тема сообщения

            $SupportDTO->setInvariable($SupportInvariableDTO);

            /**
             * Создаем сообщение с отзывом
             */

            $SupportMessageDTO = new SupportMessageDTO();

            $SupportMessageDTO
                ->setName($review->getSender())     // Имя пользователя
                ->setMessage($review->getText())    // Текст сообщения
                ->setExternal($review->getId())     // Внешний (avito) ID сообщения
                ->setDate($review->getCreated())    // Дата сообщения
                ->setInMessage();                   // Входящее сообщение

            $SupportDTO->addMessage($SupportMessageDTO);


            /**
             * Сохраняем новый отзыв
             */

            $handle = $this->supportHandler->handle($SupportDTO);

            if($handle instanceof Support)
            {
                $this->logger->info(
                    sprintf('Добавили новый отзыв %s', $review->getId()),
                    [self::class.':'.__LINE__],
                );

                $Deduplicator->save();
                unset($Deduplicator);

                continue;
            }

            $this->logger->critical(
                sprintf('avito-support: Ошибка %s при добавлении отзыва', $handle),
                [self::class.':'.__LINE__],
            );
        }

        $isExecuted->delete();
    }
}
