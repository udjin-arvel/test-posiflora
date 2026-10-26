<?php

namespace App\Service\Import;

use App\Entity\CustomerOrder;
use App\Entity\OrderStatus;
use App\Repository\CustomerOrderRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;

final class ImportService
{
    private static int $reportSeq = 0;

    /** @var list<OrderStatus> */
    private const FINAL_STATUSES = [OrderStatus::Delivered, OrderStatus::Cancelled];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly CustomerOrderRepository $orders,
        private readonly MarketplaceOrderParser $parser,
        private readonly OrderFactory $factory,
        private readonly string $shopId,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function import(mixed $payload): array
    {
        if (!is_array($payload) || !isset($payload['orders']) || !is_array($payload['orders'])) {
            throw new InvalidImportPayloadException('Файл должен содержать массив orders');
        }

        return $this->em->wrapInTransaction(function () use ($payload): array {
            $conn = $this->em->getConnection();
            $lockName = 'import:'.$this->shopId;
            if ((int) $conn->fetchOne('SELECT GET_LOCK(?, 3600)', [$lockName]) !== 1) {
                throw new \RuntimeException('Не удалось захватить блокировку импорта');
            }

            try {
                return $this->processBatch($payload['orders']);
            } finally {
                $conn->executeStatement('SELECT RELEASE_LOCK(?)', [$lockName]);
            }
        });
    }

    /**
     * @param list<mixed> $rows
     *
     * @return array<string, mixed>
     */
    private function processBatch(array $rows): array
    {
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $firstSeenAt = [];
        $records = [];
        $counts = [
            'created' => 0,
            'updated' => 0,
            'unchanged' => 0,
            'duplicate' => 0,
            'rejected' => 0,
        ];

        foreach ($rows as $i => $raw) {
            $index = $i + 1;
            $rawId = is_array($raw) ? $this->externalIdFromRaw($raw) : null;

            if ($rawId !== null) {
                if (isset($firstSeenAt[$rawId])) {
                    $records[] = [
                        'index' => $index,
                        'externalId' => $rawId,
                        'outcome' => 'duplicate',
                        'issues' => [[
                            'code' => 'duplicate_in_batch',
                            'message' => 'Повтор '.$rawId.' в этом файле — обработана только запись №'.$firstSeenAt[$rawId],
                        ]],
                    ];
                    ++$counts['duplicate'];
                    continue;
                }
                $firstSeenAt[$rawId] = $index;
            }

            $parsed = $this->parser->parse($raw);
            if ($parsed['ok'] === false) {
                $records[] = [
                    'index' => $index,
                    'externalId' => $rawId,
                    'outcome' => 'rejected',
                    'issues' => $parsed['errors'],
                ];
                ++$counts['rejected'];
                continue;
            }

            $record = $this->applyParsed($parsed['value'], $index, $now);
            $records[] = $record;
            ++$counts[$record['outcome']];
        }

        return [
            'id' => sprintf('imp-%s-%d', base_convert((string) time(), 10, 36), ++self::$reportSeq),
            'finishedAt' => $now->format(\DateTimeInterface::ATOM),
            'total' => count($rows),
            'counts' => $counts,
            'records' => $records,
        ];
    }

    /**
     * @param array<string, mixed> $raw
     */
    private function externalIdFromRaw(array $raw): ?string
    {
        if (!isset($raw['id']) || !is_string($raw['id'])) {
            return null;
        }
        $trimmed = trim($raw['id']);

        return $trimmed === '' ? null : $trimmed;
    }

    /**
     * @return array<string, mixed>
     */
    private function applyParsed(ParsedOrder $parsed, int $index, \DateTimeImmutable $now): array
    {
        $existing = $this->orders->findOneByShopAndExternalId($this->shopId, $parsed->externalId);
        if ($existing === null) {
            try {
                $order = $this->factory->createNew($this->shopId, $parsed, $now);
                $this->em->persist($order);
                $this->em->flush();

                return $this->createdRecord($index, $order);
            } catch (UniqueConstraintViolationException) {
                $this->em->clear();
                $existing = $this->orders->findOneByShopAndExternalId($this->shopId, $parsed->externalId);
                if ($existing === null) {
                    throw new \RuntimeException('Конфликт уникальности без существующего заказа');
                }
            }
        }

        return $this->updateExisting($existing, $parsed, $index, $now);
    }

    /**
     * @return array<string, mixed>
     */
    private function updateExisting(
        CustomerOrder $existing,
        ParsedOrder $parsed,
        int $index,
        \DateTimeImmutable $now,
    ): array {
        $base = [
            'index' => $index,
            'externalId' => $parsed->externalId,
            'needsReview' => $existing->needsReview(),
        ];

        if ($existing->getStatus() === $parsed->status) {
            return $base + [
                'outcome' => 'unchanged',
                'status' => $existing->getStatus()->value,
                'issues' => [[
                    'code' => 'status_unchanged',
                    'message' => 'Уже загружен, статус «'.OrderStatusLabel::label($existing->getStatus()).'» не изменился',
                ]],
            ];
        }

        if (in_array($existing->getStatus(), self::FINAL_STATUSES, true)) {
            return $base + [
                'outcome' => 'unchanged',
                'status' => $existing->getStatus()->value,
                'issues' => [[
                    'code' => 'final_status_locked',
                    'message' => 'Статус «'.OrderStatusLabel::label($existing->getStatus()).'» финальный — в «'
                        .OrderStatusLabel::label($parsed->status).'» не откатываем',
                ]],
            ];
        }

        $previous = $existing->getStatus();
        $existing->setStatus($parsed->status);
        $existing->setStatusUpdatedAt($now);
        $this->em->flush();

        return $base + [
            'outcome' => 'updated',
            'status' => $parsed->status->value,
            'previousStatus' => $previous->value,
            'issues' => [[
                'code' => 'status_changed',
                'message' => 'Статус: «'.OrderStatusLabel::label($previous).'» → «'.OrderStatusLabel::label($parsed->status).'»',
            ]],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function createdRecord(int $index, CustomerOrder $order): array
    {
        $issues = [];
        if ($order->needsReview()) {
            $issues[] = [
                'code' => 'needs_review',
                'message' => $order->getDeclaredTotalKop() === null
                    ? 'Нужна проверка: в файле нет итоговой суммы, считаем по позициям'
                    : 'Нужна проверка: в файле '.$this->formatMoney((int) $order->getDeclaredTotalKop())
                    .', по позициям '.$this->formatMoney($order->getItemsTotalKop()).' — считаем по позициям',
            ];
        }

        return [
            'index' => $index,
            'externalId' => $order->getExternalId(),
            'outcome' => 'created',
            'status' => $order->getStatus()->value,
            'needsReview' => $order->needsReview(),
            'issues' => $issues,
        ];
    }

    private function formatMoney(int $kop): string
    {
        return number_format($kop / 100, 2, ',', ' ').' ₽';
    }
}
