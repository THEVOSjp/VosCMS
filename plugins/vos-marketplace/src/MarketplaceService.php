<?php

declare(strict_types=1);

namespace VosMarketplace;

use VosMarketplace\Models\MarketplaceItem;
use VosMarketplace\Models\MarketplaceOrder;
use VosMarketplace\Models\OrderItem;
use VosMarketplace\Models\License;
use VosMarketplace\Models\MarketplaceCategory;

class MarketplaceService
{
    private \PDO $pdo;
    private string $prefix;

    public function __construct(\PDO $pdo, string $prefix = 'rzx_')
    {
        $this->pdo = $pdo;
        $this->prefix = $prefix;
    }

    /**
     * 카탈로그 탐색
     */
    public function browse(array $filters = []): array
    {
        $items = MarketplaceItem::search($filters);
        $categories = MarketplaceCategory::active();
        $total = $this->countItems($filters);

        return [
            'items' => $items,
            'categories' => $categories,
            'total' => $total,
            'filters' => $filters,
        ];
    }

    /**
     * 필터 기준 아이템 수
     */
    private function countItems(array $filters = []): int
    {
        $q = MarketplaceItem::query()->where('status', 'active');

        if (!empty($filters['type'])) {
            $q->where('type', $filters['type']);
        }
        if (!empty($filters['category_id'])) {
            $q->where('category_id', $filters['category_id']);
        }
        if (isset($filters['free']) && $filters['free']) {
            $q->where('price', 0);
        }

        return (int) $q->count();
    }

    /**
     * 주문 생성 (단일 아이템)
     */
    public function createOrder(string $adminId, int $itemId): array
    {
        $item = MarketplaceItem::find($itemId);
        if (!$item) {
            return ['success' => false, 'message' => 'Item not found'];
        }

        if ($item->status !== 'active') {
            return ['success' => false, 'message' => 'Item is not available'];
        }

        // 이미 구매했는지 확인
        if (OrderItem::hasPurchased($adminId, $itemId)) {
            return ['success' => false, 'message' => 'Already purchased'];
        }

        $price = $item->effectivePrice();
        $locale = $_SESSION['locale'] ?? 'ko';

        // 주문 생성
        $order = new MarketplaceOrder();
        $order->admin_id = $adminId;
        $order->subtotal = $price;
        $order->total = $price;
        $order->currency = $item->currency;
        $order->status = $price <= 0 ? 'paid' : 'pending';
        if ($price <= 0) {
            $order->paid_at = date('Y-m-d H:i:s');
        }
        $order->metadata = ['type' => 'marketplace'];

        if (!$order->save()) {
            return ['success' => false, 'message' => 'Failed to create order'];
        }

        // 주문 항목 생성
        $orderItem = new OrderItem();
        $orderItem->order_id = $order->id;
        $orderItem->item_id = $item->id;
        $orderItem->item_name = $item->localizedName($locale);
        $orderItem->item_type = $item->type;
        $orderItem->item_slug = $item->slug;
        $orderItem->price = $price;
        $orderItem->save();

        // 무료 아이템이면 바로 라이선스 발급
        if ($price <= 0) {
            $licenseService = new LicenseService($this->pdo, $this->prefix);
            $licenseService->generateForOrder($order);
        }

        return [
            'success' => true,
            'order' => $order,
            'is_free' => $price <= 0,
        ];
    }

    /**
     * 결제 완료 처리
     */
    public function confirmPayment(string $orderUuid, ?int $paymentId = null): array
    {
        $order = MarketplaceOrder::findByUuid($orderUuid);
        if (!$order) {
            return ['success' => false, 'message' => 'Order not found'];
        }

        if ($order->status === 'paid') {
            return ['success' => true, 'message' => 'Already confirmed'];
        }

        $order->markPaid($paymentId);

        // 라이선스 발급
        $licenseService = new LicenseService($this->pdo, $this->prefix);
        $licenseService->generateForOrder($order);

        // 다운로드 카운트 증가
        $items = $order->getItems();
        foreach ($items as $itemData) {
            $itemId = (int) ($itemData['item_id'] ?? 0);
            if ($itemId > 0) {
                MarketplaceItem::query()
                    ->where('id', $itemId)
                    ->update(['download_count' => \RzxLib\Core\Database\Connection::raw('download_count + 1')]);
            }
        }

        return ['success' => true, 'order' => $order];
    }

    /**
     * 관리자의 구매 내역 조회
     */
    public function getPurchases(string $adminId): array
    {
        $orders = MarketplaceOrder::forAdmin($adminId);
        $result = [];

        foreach ($orders as $orderData) {
            $order = MarketplaceOrder::fromArray($orderData);
            $items = OrderItem::forOrder($order->id);
            $result[] = [
                'order' => $orderData,
                'items' => $items,
            ];
        }

        return $result;
    }

    /**
     * 타입별 아이템 수 통계
     */
    public function getTypeStats(): array
    {
        $types = ['plugin', 'theme', 'widget', 'skin'];
        $stats = [];

        foreach ($types as $type) {
            $stats[$type] = (int) MarketplaceItem::query()
                ->where('status', 'active')
                ->where('type', $type)
                ->count();
        }

        return $stats;
    }
}
