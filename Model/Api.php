<?php

namespace Wexo\Instabox\Model;

use GuzzleHttp\Client;
use GuzzleHttp\ClientFactory;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Response;
use Http\Client\Exception\TransferException;
use Magento\Customer\Model\Session;
use Magento\Framework\Api\ObjectFactory;
use Magento\Framework\Api\SimpleDataObjectConverter;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\UrlInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Magento\Store\Api\StoreManagementInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Wexo\Instabox\Api\Data\ParcelShopInterface;
use Magento\Checkout\Model\Session as CheckoutSession;

class Api
{
    const AUTHENTICATION_URI = 'https://oauth.instabox.se/v1/token';
    const AVAILABILITY_URI = 'https://availability.instabox.se/v3/availability';
    const PREBOOKING_URI = 'https://webshopintegrations.instabox.se/v2/prebookings';
    const ORDER_URI = 'https://webshopintegrations.instabox.se/v2/orders';
    const RETURN_URI = 'https://webshopintegrations.instabox.se/returns';
    const TRACKING_URI = 'https://track.instabox.io';
    const CACHE_KEY_ACCESS_TOKEN = 'instabox_access_token';

    /**
     * @var ClientFactory
     */
    private $clientFactory;

    /**
     * @var UrlInterface
     */
    private $url;

    /**
     * @var Client
     */
    private $client = null;

    /**
     * @var Json
     */
    private $jsonSerializer;

    /**
     * @var ObjectFactory
     */
    private $objectFactory;

    /**
     * @var Config
     */
    private $config;
    /**
     * @var CacheInterface
     */
    private $cache;
    /**
     * @var Session
     */
    private $session;
    /**
     * @var StoreManagementInterface
     */
    private $storeManager;
    /**
     * @var CheckoutSession
     */
    private $checkoutSession;
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Api constructor.
     * @param ClientFactory $clientFactory
     * @param UrlInterface $url
     * @param Json $jsonSerializer
     * @param ObjectFactory $objectFactory
     * @param CacheInterface $cache
     * @param StoreManagerInterface $storeManager
     * @param CheckoutSession $checkoutSession
     * @param Session $session
     * @param LoggerInterface $logger
     * @param Config $config
     */
    public function __construct(
        ClientFactory $clientFactory,
        UrlInterface $url,
        Json $jsonSerializer,
        ObjectFactory $objectFactory,
        CacheInterface $cache,
        StoreManagerInterface $storeManager,
        CheckoutSession $checkoutSession,
        Session $session,
        LoggerInterface $logger,
        \Wexo\Instabox\Model\Config $config
    ) {
        $this->clientFactory = $clientFactory;
        $this->url = $url;
        $this->jsonSerializer = $jsonSerializer;
        $this->objectFactory = $objectFactory;
        $this->config = $config;
        $this->cache = $cache;
        $this->session = $session;
        $this->storeManager = $storeManager;
        $this->checkoutSession = $checkoutSession;
        $this->logger = $logger;
    }

    /**
     * @param $countryCode
     * @param $postalCode
     * @param $amount
     * @return array|false
     */
    public function getParcelShops(
        $email,
        $phone,
        $street,
        $zip,
        $city,
        $countryCode,
        $currencyCode,
        $items,
        $grandTotal
    ) {
        $this->session->setInstaboxShowAsOption(false);
        $body = $this->getAvailabilityBody(
            $email,
            $phone,
            $street,
            $zip,
            $city,
            $countryCode,
            $currencyCode,
            $items,
            $grandTotal
        );
        return $this->request(function (Client $client) use ($body) {
            return $client->post(self::AVAILABILITY_URI, [
                'json' => $body
            ]);
        }, function (Response $response, $content) {
            $this->saveShowAsOption($content);
            $this->saveAvailabilityToken($content);
            return $this->mapParcelShops($content);
        });
    }

    public function saveShowAsOption($content)
    {
        if (isset($content['availability'])) {
            $availability = $content['availability'];
            $type = reset($availability);
            $showAsOption = isset($type['show_as_option']) ? $type['show_as_option'] : false;
            $this->session->setInstaboxShowAsOption($showAsOption);
        }
    }

    public function getShowAsOption()
    {
        return $this->session->getInstaboxShowAsOption();
    }

    public function saveAvailabilityToken($content)
    {
        $this->session->setInstaboxAvailabilityToken($content['availability_token'] ?? '');
    }

    public function getAvailabilityToken()
    {
        return $this->session->getInstaboxAvailabilityToken();
    }

    protected function getAvailabilityBody(
        $email,
        $phone,
        $street,
        $zip,
        $city,
        $countryCode,
        $currencyCode,
        $items,
        $grandTotal
    ) {
        $products = [];
        $weight = 0;
        foreach ($items as $item) {
            if (isset($item['weight'])) {
                $weight += floatval($item['weight']);
            }
            $products[] = [
                'quantity' => $item['qty'],
                'product_id' => $item['sku'],
//                'packages' => [
//                    [
//                        'width' => 1,
//                        'height' => 1,
//                        'depth' => 1,
//                        'weight' => 1,
//                    ],
//                ],
            ];
        }
        return [
            'recipient' => [
                'email' => $email ?? '',
//                'national_identification_number' => '199001012316',
                'mobile_phone_number' => $phone ?? '',
                'street' => $street ?? '',
                'zip' => $zip ?? '',
                'city' => $city ?? '',
                'country_code' => $countryCode,
            ],
            'services' => [
                [
                    'service_type' => 'EXPRESS',
                    'options' => [
                        'num_delivery_options' => 25,
                        'num_dispatch_options' => 5,
                        'response_fields' => [
                            'OPENHOURS',
                            'DISTANCE',
                        ],
                        'might_require_id' => true,
                        'sort_dispatch_options_by' => [
                            [
                                'field' => 'eta',
                                'direction' => 'DESC',
                            ],
                            [
                                'field' => 'ready_to_pack',
                                'direction' => 'ASC',
                            ],
                        ],
                    ],
                ],
            ],
            'details' => [
                'total_value' => $grandTotal,
                'total_weight' => $weight,
                'package' => [
                    'width' => 15,
                    'height' => 15,
                    'depth' => 15,
                    'weight' => $weight ?? 100,
                    'type' => 'BOX',
                ],
                'products' => [
                    $products
                ],
            ],
            'options' => [
                'units' => [
                    'dimensions' => 'cm',
                    'weight' => 'g',
                    'currency' => $currencyCode,
                ],
            ],
        ];
    }

    public function authenticate()
    {
        $client = $this->clientFactory->create([
            'config' => [
                'time_out' => 2.0,
            ]
        ]);
        $options = [
            'grant_type' => 'client_credentials',
            'client_id' => $this->config->getClientId(),
            'client_secret' => $this->config->getClientSecret()
        ];
        $response = $client->post(self::AUTHENTICATION_URI, [
            'json' => $options
        ]);
        $content = $this->jsonSerializer->unserialize($response->getBody()->__toString());
        if ($content['status'] === 'ok') {
            $token = $content['token'];
            $this->cache->remove($this->getCacheKey());
            $this->cache->save($token, $this->getCacheKey(), []);
            return $token;
        }
    }

    public function getCacheKey()
    {
        return self::CACHE_KEY_ACCESS_TOKEN . '_' . $this->storeManager->getStore()->getId();
    }

    /**
     * @param callable $func
     * @param callable $transformer
     * @return mixed
     */
    public function request(callable $func, callable $transformer = null)
    {
        try {
            /** @var Response $response */
            $response = $func($this->getClient());

            if ($response->getStatusCode() >= 200 && $response->getStatusCode() <= 299) {
                $content = $this->jsonSerializer->unserialize($response->getBody()->__toString());
                return $transformer === null ? $content : $transformer($response, $content);
            }

            return false;
        } catch (ClientException $exception) {
            $body = $exception->getResponse()->getBody();
            $this->logger->error($exception->getMessage(), [
                'body' => $body
            ]);
            throw $exception;
        }
    }

    public function getFirstParcelShopName()
    {
        $quote = $this->checkoutSession->getQuote();
        $shippingAddress = $quote->getShippingAddress();
        $zip = $shippingAddress->getPostcode();
        if (empty($zip)) {
            return '';
        }
        $street = $shippingAddress->getStreet() ?? [];
        $street = isset($street[0]) ? $street[0] : false;
        $parcelShops = $this->getParcelShops(
            $quote->getCustomerEmail() ?? '',
            $shippingAddress->getTelephone() ?? '',
            $street ?? '',
            $zip,
            $shippingAddress->getCity() ?? '',
            $shippingAddress->getCountryId() ?? '',
            $quote->getStoreCurrencyCode() ?? '',
            $quote->getItems() ?? [],
            $quote->getGrandTotal() ?? 0
        );
        if (!empty($parcelShops) && isset($parcelShops[0])) {
            return $parcelShops[0]->getCompanyName();
        }
        return '';
    }

    /**
     * Creates a Prebooking in Instabox
     * https://www.instadocs.se/docs#section-5
     *
     * @param  $parcelShop
     * @param  $quote
     * @param  $order
     * @return void
     */
    public function createPreBooking($parcelShop, $quote, $order)
    {
        $availabilityToken = $this->getAvailabilityToken();
        $method = explode('_', $order->getShippingMethod());
        $serviceType = isset($method[1]) ? strtoupper($method[1]) : false;
        if (!$serviceType) {
            $this->logger->error(
                'Instabox API No service type found on shipping method',
                [
                    'method' => $method
                ]
            );
        }

        $deliveryOption = [
            'sort_code' => $parcelShop->getNumber()
        ];

        $body = [
            "prebooking" => [
                'availability_token' => $availabilityToken,
                'service_type' => $serviceType,
                'delivery_option' => $deliveryOption
            ]
        ];

        $this->logger->debug('InstaBox CreatePreBooking preflight', [
            'body' => $body,
            'parcelshop' => $parcelShop,
            'quote' => $quote,
            'order' => $order
        ]);
        try {
            return $this->request(function (Client $client) use ($body) {
                return $client->post(self::PREBOOKING_URI, [
                    'json' => $body
                ]);
            }, function (Response $response, $content) {
                $this->logger->debug(
                    'Instabox CreatePreBooking Response',
                    [
                        'response' => $response,
                        'content' => $content
                    ]
                );
                return $content;
            });
        } catch (\Throwable $t) {
            $this->logger->error('Instabox CreatePreBooking ' . $t->getMessage());
            throw $t;
        }
    }

    /**
     * Creates an Order in Instabox
     * https://www.instadocs.se/docs#section-6
     *
     * @param OrderInterface $order
     * @param  $shipment
     * @return void
     */
    public function createBooking(OrderInterface $order, $shipment)
    {
        $shippingData = $this->jsonSerializer->unserialize($order->getData('wexo_shipping_data'));
        $instabox = isset($shippingData['instabox']) ? $shippingData['instabox'] : false;
        if (!$instabox) {
            $this->logger->error(
                'Instabox CreateOrderV2 No Instabox data',
                [
                    'shipping_data' => $shippingData
                ]
            );
            throw new LocalizedException(
                __('No Instabox Object found on Order, check the logs for more details')
            );
        }
        $parcelId = $this->config->getCustomerNumber();
        $parcelId .= str_pad($shipment->getIncrementId(), 10, '0', STR_PAD_LEFT);
        $preBooking = $instabox['prebooking']['prebooking'];
        $availabilityToken = $preBooking['availability_token'];
        $billingAddress = $order->getBillingAddress();
        $streets = $billingAddress->getStreet();
        $street = isset($streets[0]) ? $streets[0] : '';
        $body = [
            "options" => [
                "units" => [
                    "weight" => "g",
                    "currency" => $order->getOrderCurrencyCode()
                ]
            ],
            "order" => [
                "availability_token" => $availabilityToken,
                "parcel_id" => $parcelId,
                "order_number" => $order->getIncrementId(),
                "recipient" => [
                    "name" => $order->getCustomerFirstname() . ' ' . $order->getCustomerLastname(),
                    "street" => $street,
                    "zip" => $billingAddress->getPostcode(),
                    "city" => $billingAddress->getCity(),
                    "country_code" => $billingAddress->getCountryId(),
                    "mobile_phone_number" => $billingAddress->getTelephone(),
                    "home_phone_number" => $billingAddress->getTelephone(),
                    "work_phone_number" => $billingAddress->getTelephone(),
                    "email_address" => $order->getCustomerEmail()
                ],
                "sender" => [
                    "name" => $this->config->getStoreName(),
                    "street" => $this->config->getStoreStreet1(),
                    "street2" => $this->config->getStoreStreet2(),
                    "zip" => $this->config->getStoreZip(),
                    "city" => $this->config->getStoreCity(),
                    "country_code" => $this->config->getStoreCountry(),
                ],
                "service_type" => $preBooking['service_type'],
//                "storage_condition" => "NORMAL",
                /*
                    order.storage_condition Constraints
                    enum: the value of this property must be equal to one of the following values:
                    Value Explanation
                    "freezer" Package needs to remain frozen
                    "fridge" Package needs to be chilled
                    "normal" Package doesn't need to be cooled
                 */
                "delivery_option" => [
                    "sort_code" => $preBooking['delivery_option']['sort_code']
                ],
                "details" => [
                    "total_weight" => (float)$order->getWeight() ?? 0,
                    "total_value" => (float)$order->getGrandTotal()
                ],
                "identification_options" => [
                    "type" => "ANY_PERSON",
//                    "minimum_age" => 18,
//                    "name" => "Test Testson",
//                    "national_identification_number" => "199001012312",
//                    "verify_person_using" => "national_identification_number"
                ]
            ]
        ];
        $this->logger->debug(
            'Instabox CreateOrder Preflight',
            [
                'body' => $body
            ]
        );

        try {
            return $this->request(function (Client $client) use ($body) {
                return $client->post(self::ORDER_URI, [
                    'json' => $body
                ]);
            }, function (Response $response, $content) {
                $this->logger->debug(
                    'InstaBox CreateOrder Response',
                    [
                        'response' => $response,
                        'content' => $content
                    ]
                );
                return $content;
            });
        } catch (\Throwable $t) {
            $this->logger->error('Instabox CreateBooking ' . $t->getMessage());
            throw $t;
        }
    }

    /**
     * Creates returns in Instabox
     * https://www.instadocs.se/docs#section-7
     *
     * @param OrderInterface $order
     * @return void
     */
    public function createReturn(OrderInterface $order)
    {
        $body = [
            "reference_order_number" => $order->getIncrementId(),
        ];
        $this->logger->debug(
            'Instabox CreateReturn Preflight',
            [
                'body' => $body
            ]
        );

        try {
            return $this->request(function (Client $client) use ($body) {
                return $client->post(self::RETURN_URI, [
                    'json' => $body
                ]);
            }, function (Response $response, $content) {
                $this->logger->debug(
                    'Instabox CreateReturn Response',
                    [
                        'response' => $response,
                        'content' => $content
                    ]
                );
                return $content;
            });
        } catch (\Throwable $t) {
            $this->logger->error('Instabox CreateReturn ' . $t->getMessage());
            throw $t;
        }
    }

    /**
     * @return Client
     */
    public function getClient()
    {
        if ($this->client === null) {
            $token = $this->cache->load($this->getCacheKey());
            if (!$token) {
                $token = $this->authenticate();
            }
            $this->client = $this->clientFactory->create([
                'config' => [
                    'time_out' => 2.0,
                    'headers' => [
                        'Authorization' => 'Bearer ' . $token
                    ]
                ]
            ]);
        }

        return $this->client;
    }

    public $dayMapper = [
        0 => 'Monday',
        1 => 'Tuesday',
        2 => 'Wednesday',
        3 => 'Thursday',
        4 => 'Friday',
        5 => 'Saturday',
        6 => 'Sunday'
    ];

    /**
     * @param $content
     * @return array
     * @throws \Exception
     */
    protected function mapParcelShops($content)
    {
        $valid = $content['status'] === 'OK' ?? false;
        if (!$valid) {
            return [];
        }
        $availability = $content['availability'] ?? [];
        $express = $availability['EXPRESS'] ?? [];
        $dispatchOptions = $express['dispatch_options'][0] ?? [];
        $parcelShops = $dispatchOptions['delivery_options'] ?? [];
        return array_map(function ($parcelShop) {
            $openingHours = [];
            foreach ($parcelShop['openhours'] as $openHour) {
                if (isset($openHour['open_utc']) && isset($openHour['close_utc'])) {
                    $opensAt = new \DateTime($openHour['open_utc']);
                    $closesAt = new \DateTime($openHour['close_utc']);
                    $openingHours[] = [
                        'opens_at' => $opensAt->format('H:i:s'),
                        'closes_at' => $closesAt->format('H:i:s'),
                        'day' => $opensAt->format('l')
                    ];
                }
            }

            $parcelShopObject = $this->objectFactory->create(ParcelShopInterface::class, []);

            $address = $parcelShop['address'] ?? [];
            $parcelShopObject->setNumber($parcelShop['sort_code']);
            $parcelShopObject->setCompanyName($parcelShop['description']);
            $parcelShopObject->setStreetName($address['street']);
            $parcelShopObject->setZipCode($address['zip']);
            $parcelShopObject->setCity($address['city']);
            $parcelShopObject->setCountryCode($address['country']);
            $parcelShopObject->setLongitude($parcelShop['coordinates']['long']);
            $parcelShopObject->setLatitude($parcelShop['coordinates']['lat']);
            $parcelShopObject->setOpeningHours([$openingHours]);

            return $parcelShopObject;
        }, $parcelShops);
    }
}
