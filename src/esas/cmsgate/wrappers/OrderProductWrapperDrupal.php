<?php
namespace esas\cmsgate\wrappers;

use Throwable;

class OrderProductWrapperDrupal extends OrderProductSafeWrapper
{
    /**
     * @var \Drupal\commerce_order\Entity\OrderItemInterface
     */
    private $orderItem;

    /**
     * OrderProductWrapperOpencart constructor.
     * @param $orderItem
     */
    public function __construct($orderItem)
    {
        parent::__construct();
        $this->orderItem = $orderItem;
    }


    /**
     * Артикул товара
     * @throws Throwable
     * @return string
     */
    public function getInvIdUnsafe()
    {
        return $this->orderItem->getPurchasedEntity()->getSKU();
    }

    /**
     * Название или краткое описание товара
     * @throws Throwable
     * @return string
     */
    public function getNameUnsafe()
    {
        return $this->orderItem->getTitle();
    }

    /**
     * Количество товароа в корзине
     * @throws Throwable
     * @return mixed
     */
    public function getCountUnsafe()
    {
        return $this->orderItem->getQuantity();
    }

    /**
     * Цена за единицу товара
     * @throws Throwable
     * @return mixed
     */
    public function getUnitPriceUnsafe()
    {
        return $this->orderItem->getUnitPrice()->getNumber();
    }
}