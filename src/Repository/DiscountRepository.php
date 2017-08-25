<?php

namespace Repository;

use Entity\AbstractUser;
use Entity\Discount;
use Entity\LegalUser;
use Entity\NaturalUser;
use Persistence\PersistenceInterface;

class DiscountRepository
{
    /**
     * @var PersistenceInterface
     */
    private $persistence;

    /**
     * @param PersistenceInterface $persistence
     */
    public function __construct(PersistenceInterface $persistence)
    {
        $this->persistence = $persistence;
    }

    /**
     * @param int $userId
     * @return Discount|null
     */
    public function find(int $userId) :? Discount
    {
        $discounts = $this->persistence->findAll('discount');

        /**
         * @var Discount $discount
         */
        foreach ($discounts as $discount) {
            if ($discount->getUser()->getId() === $userId) {
                return $discount;
            }
        }

        return null;
    }

    /**
     * @param AbstractUser $user
     * @param \DateTime $periodStart
     * @param \DateTime $periodEnd
     * @param int $amount
     */
    public function create(AbstractUser $user, \DateTime $periodStart, \DateTime $periodEnd, int $amount) : void
    {
        $this->persistence->save('discount', new Discount($user, $periodStart, $periodEnd, $amount));
    }
}
