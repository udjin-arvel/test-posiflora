<?php

namespace App\Service\Import;

use App\Entity\DeliveryType;
use App\Entity\OrderStatus;
use App\Repository\DeliveryTariffRepository;

final class MarketplaceOrderParser
{
    private const SHOP_UTC_OFFSET = '+03:00';

    /** @var array<string, OrderStatus> */
    private const STATUS_MAP = [
        'NEW' => OrderStatus::New,
        'ACCEPTED' => OrderStatus::Accepted,
        'IN_DELIVERY' => OrderStatus::Delivering,
        'DONE' => OrderStatus::Delivered,
        'CANCELED' => OrderStatus::Cancelled,
    ];

    public function __construct(
        private readonly PhoneNormalizer $phones,
        private readonly DeliveryTariffRepository $tariffs,
    ) {
    }

    /**
     * @return array{ok: true, value: ParsedOrder}|array{ok: false, errors: list<array{code: string, message: string}>}
     */
    public function parse(mixed $raw): array
    {
        if (!is_array($raw)) {
            return [
                'ok' => false,
                'errors' => [['code' => 'invalid_record', 'message' => 'Запись не похожа на заказ: ожидался объект']],
            ];
        }

        $errors = [];
        $missing = static function (string $label) use (&$errors): void {
            $errors[] = ['code' => 'missing_field', 'message' => 'Не заполнено обязательное поле «'.$label.'»'];
        };

        $externalId = $this->str($raw['id'] ?? null);
        if ($externalId === null) {
            $missing('id');
        }

        $rawStatus = $this->str($raw['status'] ?? null);
        $status = null;
        if ($rawStatus === null) {
            $missing('статус');
        } else {
            $status = self::STATUS_MAP[$rawStatus] ?? null;
            if ($status === null) {
                $errors[] = ['code' => 'unknown_status', 'message' => 'Неизвестный статус «'.$rawStatus.'»'];
            }
        }

        $customer = is_array($raw['customer'] ?? null) ? $raw['customer'] : [];
        $name = $this->str($customer['name'] ?? null);
        if ($name === null) {
            $missing('имя покупателя');
        }

        $rawPhone = $customer['phone'] ?? null;
        if (is_int($rawPhone) || is_float($rawPhone)) {
            $rawPhone = (string) $rawPhone;
        }
        $phone = null;
        if (!is_string($rawPhone) || trim($rawPhone) === '') {
            $missing('телефон покупателя');
        } else {
            $phone = $this->phones->normalize($rawPhone);
            if ($phone === null) {
                $errors[] = [
                    'code' => 'invalid_phone',
                    'message' => 'Телефон «'.$rawPhone.'» не приводится к формату +7XXXXXXXXXX',
                ];
            }
        }

        $items = [];
        $rawItems = $raw['items'] ?? null;
        if (!is_array($rawItems) || $rawItems === []) {
            $missing('позиции заказа');
        } else {
            foreach ($rawItems as $i => $it) {
                $n = $i + 1;
                $bad = static function (string $msg) use (&$errors, $n): void {
                    $errors[] = ['code' => 'invalid_items', 'message' => 'Позиция '.$n.': '.$msg];
                };
                if (!is_array($it)) {
                    $bad('ожидался объект');
                    continue;
                }
                $itemName = $this->str($it['name'] ?? null) ?? $this->str($it['sku'] ?? null);
                $qty = $it['qty'] ?? null;
                $price = $it['price'] ?? null;
                $valid = true;
                if ($itemName === null) {
                    $bad('нет названия');
                    $valid = false;
                }
                if (!is_int($qty) || $qty <= 0) {
                    $bad('количество должно быть целым числом больше нуля');
                    $valid = false;
                }
                if (!is_int($price) && !is_float($price)) {
                    $bad('некорректная цена');
                    $valid = false;
                } elseif (!is_finite((float) $price) || (float) $price < 0) {
                    $bad('некорректная цена');
                    $valid = false;
                }
                if ($valid) {
                    $items[] = [
                        'sku' => $this->str($it['sku'] ?? null),
                        'name' => $itemName,
                        'qty' => $qty,
                        'priceKop' => (int) round((float) $price * 100),
                    ];
                }
            }
        }

        $d = is_array($raw['delivery'] ?? null) ? $raw['delivery'] : null;
        $deliveryTypeRaw = $d !== null ? $this->str($d['type'] ?? null) : null;
        $deliveryType = null;
        $district = null;
        $address = null;
        $deliveryDate = null;
        $timeFrom = null;
        $timeTo = null;

        if ($d === null || $deliveryTypeRaw === null) {
            $missing('тип доставки');
        } elseif ($deliveryTypeRaw === 'pickup') {
            $deliveryType = DeliveryType::Pickup;
        } elseif ($deliveryTypeRaw === 'delivery') {
            $deliveryType = DeliveryType::Delivery;
            $district = $this->str($d['district'] ?? null);
            $address = $this->str($d['address'] ?? null);
            $date = $this->str($d['date'] ?? null);
            $tf = $this->str($d['time_from'] ?? null);
            $tt = $this->str($d['time_to'] ?? null);
            if ($district === null) {
                $missing('район доставки');
            }
            if ($address === null) {
                $missing('адрес доставки');
            }
            $intervalOk = false;
            if ($date === null || $tf === null || $tt === null) {
                $missing('интервал доставки');
            } elseif (
                !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)
                || !preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $tf)
                || !preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $tt)
                || $tt <= $tf
            ) {
                $errors[] = [
                    'code' => 'invalid_interval',
                    'message' => 'Интервал доставки «'.$date.' '.$tf.'–'.$tt.'» указан некорректно',
                ];
            } else {
                $intervalOk = true;
            }
            if ($district !== null && $this->tariffs->findByCode($district) === null) {
                $errors[] = [
                    'code' => 'unknown_district',
                    'message' => 'Нет тарифа доставки для района «'.$district.'»',
                ];
            }
            if ($intervalOk && $district !== null && $address !== null && $date !== null && $tf !== null && $tt !== null) {
                $deliveryDate = \DateTimeImmutable::createFromFormat('Y-m-d', $date) ?: null;
                $timeFrom = \DateTimeImmutable::createFromFormat('H:i', $tf) ?: null;
                $timeTo = \DateTimeImmutable::createFromFormat('H:i', $tt) ?: null;
            }
        } else {
            $errors[] = [
                'code' => 'invalid_delivery_type',
                'message' => 'Неизвестный тип доставки «'.$deliveryTypeRaw.'»',
            ];
        }

        $createdRaw = $this->str($raw['created_at'] ?? null);
        $marketplaceCreatedAt = null;
        if ($createdRaw !== null) {
            $marketplaceCreatedAt = $this->parseCreatedAt($createdRaw);
            if ($marketplaceCreatedAt === null) {
                $errors[] = [
                    'code' => 'invalid_created_at',
                    'message' => 'Дата создания «'.$createdRaw.'» не распознана',
                ];
            }
        } elseif ($deliveryTypeRaw === 'delivery') {
            $missing('дата создания (нужна для расчёта срочности)');
        }

        $declaredTotalKop = null;
        if (isset($raw['total']) && is_int($raw['total'])) {
            $declaredTotalKop = (int) round($raw['total'] * 100);
        } elseif (isset($raw['total']) && is_float($raw['total']) && is_finite($raw['total'])) {
            $declaredTotalKop = (int) round($raw['total'] * 100);
        }

        if (
            $errors !== []
            || $externalId === null
            || $status === null
            || $name === null
            || $phone === null
            || $deliveryType === null
        ) {
            return ['ok' => false, 'errors' => $errors];
        }

        return [
            'ok' => true,
            'value' => new ParsedOrder(
                $externalId,
                $status,
                $marketplaceCreatedAt,
                $name,
                $phone,
                $deliveryType,
                $district,
                $address,
                $deliveryDate,
                $timeFrom,
                $timeTo,
                $items,
                $declaredTotalKop,
            ),
        ];
    }

    private function str(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }
        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function parseCreatedAt(string $raw): ?\DateTimeImmutable
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}(:\d{2}(\.\d+)?)?(Z|[+-]\d{2}:\d{2})?$/', $raw)) {
            return null;
        }
        $withTz = preg_match('/(Z|[+-]\d{2}:\d{2})$/', $raw) ? $raw : $raw.self::SHOP_UTC_OFFSET;
        try {
            return (new \DateTimeImmutable($withTz))->setTimezone(new \DateTimeZone('UTC'));
        } catch (\Exception) {
            return null;
        }
    }
}
