<?php

declare(strict_types=1);

namespace BaksDev\Avito\Support\Messenger\NewMessage\NewSupportReview;

use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use Symfony\Component\Validator\Constraints as Assert;

/** @see NewSupportReviewMessage */
final class NewSupportReviewMessage
{
    /** Идентификатор */
    #[Assert\Uuid]
    private UserProfileUid $profile;

    public function __construct(UserProfileUid|string $profile)
    {

        $this->profile = $profile;
    }

    public function getProfile(): UserProfileUid
    {
        return $this->profile;
    }

}