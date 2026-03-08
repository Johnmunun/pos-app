<?php

namespace Src\Infrastructure\GlobalCommerce\Procurement\Persistence;

use Src\Domain\GlobalCommerce\Procurement\Entities\Purchase;
use Src\Domain\GlobalCommerce\Procurement\Repositories\PurchaseRepositoryInterface;
use Src\Infrastructure\GlobalCommerce\Procurement\Models\PurchaseModel;
use Src\Infrastructure\GlobalCommerce\Procurement\Models\PurchaseLineModel;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Ramsey\Uuid\Uuid;

class EloquentPurchaseRepository implements PurchaseRepositoryInterface
{
    public function save(Purchase $purchase): void
    {
        // S'assurer que shop_id est un entier (car la colonne est unsignedBigInteger)
        $shopId = (int) $purchase->getShopId();
        
        \Log::debug('EloquentPurchaseRepository::save - Début sauvegarde', [
            'purchase_id' => $purchase->getId(),
            'shop_id' => $shopId,
            'shop_id_original' => $purchase->getShopId(),
            'supplier_id' => $purchase->getSupplierId(),
            'status' => $purchase->getStatus(),
            'total_amount' => $purchase->getTotalAmount(),
            'lines_count' => count($purchase->getLines()),
        ]);
        
        try {
            // Utiliser une transaction pour garantir la cohérence
            \DB::beginTransaction();
            
            // Créer ou mettre à jour l'achat
            $purchaseModel = PurchaseModel::updateOrCreate(
                ['id' => $purchase->getId()],
                [
                    'shop_id' => $shopId, // Convertir en entier
                    'supplier_id' => $purchase->getSupplierId(),
                    'status' => $purchase->getStatus(),
                    'total_amount' => $purchase->getTotalAmount(),
                    'currency' => $purchase->getCurrency(),
                    'expected_at' => $purchase->getExpectedAt(),
                    'received_at' => $purchase->getReceivedAt(),
                    'notes' => $purchase->getNotes(),
                ]
            );
            
            \Log::debug('EloquentPurchaseRepository::save - Achat créé/mis à jour', [
                'id' => $purchaseModel->id,
                'shop_id' => $purchaseModel->shop_id,
                'wasRecentlyCreated' => $purchaseModel->wasRecentlyCreated,
            ]);
            
            // Supprimer les anciennes lignes
            $deletedCount = PurchaseLineModel::where('purchase_id', $purchase->getId())->delete();
            \Log::debug('EloquentPurchaseRepository::save - Lignes supprimées', [
                'deleted_count' => $deletedCount,
            ]);
            
            // Créer les nouvelles lignes
            $linesCreated = 0;
            foreach ($purchase->getLines() as $index => $line) {
                try {
                    PurchaseLineModel::create([
                        'id' => Uuid::uuid4()->toString(),
                        'purchase_id' => $purchase->getId(),
                        'product_id' => $line['product_id'],
                        'ordered_quantity' => $line['ordered_quantity'],
                        'received_quantity' => $line['received_quantity'] ?? 0,
                        'unit_cost' => $line['unit_cost'],
                        'line_total' => $line['line_total'],
                        'product_name' => $line['product_name'],
                    ]);
                    $linesCreated++;
                } catch (\Exception $e) {
                    \Log::error('EloquentPurchaseRepository::save - Erreur création ligne', [
                        'index' => $index,
                        'line' => $line,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                    throw $e;
                }
            }
            
            \Log::debug('EloquentPurchaseRepository::save - Lignes créées', [
                'created_count' => $linesCreated,
            ]);
            
            // Commit la transaction
            \DB::commit();
            
            // Vérifier que l'achat a bien été sauvegardé
            $saved = PurchaseModel::with('lines')->find($purchase->getId());
            if ($saved) {
                \Log::debug('EloquentPurchaseRepository::save - Achat sauvegardé avec succès', [
                    'id' => $saved->id,
                    'shop_id' => $saved->shop_id,
                    'lines_count' => $saved->lines->count(),
                ]);
            } else {
                \Log::error('EloquentPurchaseRepository::save - Achat non trouvé après sauvegarde', [
                    'purchase_id' => $purchase->getId(),
                ]);
                throw new \RuntimeException('L\'achat n\'a pas pu être sauvegardé.');
            }
            
        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('EloquentPurchaseRepository::save - Erreur lors de la sauvegarde', [
                'purchase_id' => $purchase->getId(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    public function findById(string $id): ?Purchase
    {
        try {
            $model = PurchaseModel::with('lines')->findOrFail($id);
            return $this->toDomainEntity($model);
        } catch (ModelNotFoundException) {
            return null;
        }
    }

    /** @return Purchase[] */
    public function findByShop(string $shopId, int $limit = 50, int $offset = 0): array
    {
        $models = PurchaseModel::with(['lines', 'supplier'])
            ->where('shop_id', $shopId)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->offset($offset)
            ->get();

        return $models->map(fn ($m) => $this->toDomainEntity($m))->toArray();
    }

    private function toDomainEntity(PurchaseModel $model): Purchase
    {
        $lines = $model->lines->map(fn ($l) => [
            'product_id' => $l->product_id,
            'product_name' => $l->product_name,
            'ordered_quantity' => (float) $l->ordered_quantity,
            'received_quantity' => (float) $l->received_quantity,
            'unit_cost' => (float) $l->unit_cost,
            'line_total' => (float) $l->line_total,
        ])->toArray();

        return new Purchase(
            $model->id,
            (string) $model->shop_id,
            $model->supplier_id,
            $model->status,
            (float) $model->total_amount,
            $model->currency,
            $model->expected_at,
            $model->received_at,
            $model->notes,
            $lines,
            $model->created_at
        );
    }
}
