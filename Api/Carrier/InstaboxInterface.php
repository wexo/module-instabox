<?php

namespace Wexo\Instabox\Api\Carrier;

use Wexo\Shipping\Api\Carrier\CarrierInterface;

interface InstaboxInterface extends CarrierInterface
{
    const TYPE_NAME = 'instabox';

    /**
     * @param string $email
     * @param string $phone
     * @param string $street
     * @param string $zip
     * @param string $city
     * @param string $country_code
     * @param string $currency_code
     * @param mixed $items
     * @param float $grand_total
     * @return \Wexo\Instabox\Api\Data\ParcelShopInterface[]
     */
    public function getParcelShops(
        $email,
        $phone,
        $street,
        $zip,
        $city,
        $country_code,
        $currency_code,
        $items,
        $grand_total
    );
}
