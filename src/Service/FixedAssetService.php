<?php

namespace App\Service;

use App\Domain\Accounting\FixedAsset;
use App\Domain\Accounting\FixedAssetDepreciationEntry;
use App\Domain\Invoicing\ExpenseCategory;
use App\Domain\Invoicing\PurchaseInvoiceLine;
use App\Repository\AccountRepository;
use App\Repository\FixedAssetRepository;
use InvalidArgumentException;

/**
 * Основни средства (Фаза 8). Стандардните сметки за амортизација се
 * хардкодирани — за разлика од банкарските трансакции (PaymentMatchingService),
 * тука нема легитимна причина корисникот да бира различна сметка по
 * амортизациски запис, секогаш е истата двојка сметки.
 */
class FixedAssetService
{
    private const ACCOUNT_DEPRECIATION_EXPENSE = '430';
    private const ACCOUNT_ACCUMULATED_DEPRECIATION = '029';

    private FixedAssetRepository $assets;
    private AccountRepository $accounts;
    private LedgerService $ledger;

    public function __construct(
        ?FixedAssetRepository $assets = null,
        ?AccountRepository $accounts = null,
        ?LedgerService $ledger = null
    ) {
        $this->assets = $assets ?? new FixedAssetRepository();
        $this->accounts = $accounts ?? new AccountRepository();
        $this->ledger = $ledger ?? new LedgerService();
    }

    /**
     * Создава основно средство од линија на влезна фактура (повикано од
     * PurchaseInvoiceService::post() за секоја капитализабилна линија).
     */
    public function createFromPurchaseInvoiceLine(
        int $purchaseInvoiceId,
        string $supplierNumber,
        PurchaseInvoiceLine $line,
        ExpenseCategory $category,
        string $purchaseDate
    ): int {
        if (!$category->defaultAnnualRate) {
            throw new InvalidArgumentException("Категоријата „{$category->name}“ нема стапка на амортизација — не може да се создаде основно средство.");
        }

        $vatAmount = number_format($line->vatAmount(), 2, '.', '');
        $purchaseValue = $category->vatDeductible === 'full'
            ? $line->lineTotal
            : bcadd($line->lineTotal, $vatAmount, 2);

        $name = $line->description ?: "{$category->name} — фактура {$supplierNumber}";

        return $this->assets->create(new FixedAsset(
            $name,
            $line->accountId,
            $purchaseInvoiceId,
            (int) $line->id,
            $purchaseDate,
            $purchaseValue,
            $category->defaultAnnualRate
        ));
    }

    /**
     * Права-линиска месечна амортизација за сите активни средства.
     * Идемпотентно — повторно извршување за истиот период не создава
     * дупликат (fixed_asset_depreciation_entries UNIQUE(asset, period)).
     * Сите засегнати средства се книжат преку ЕДЕН заеднички journal entry
     * (стандардна пракса, не еден запис по средство).
     *
     * @return array{count: int, total: string}
     */
    public function runDepreciation(string $periodDate): array
    {
        $depreciationAccount = $this->accounts->findByCode(self::ACCOUNT_DEPRECIATION_EXPENSE);
        $accumulatedAccount = $this->accounts->findByCode(self::ACCOUNT_ACCUMULATED_DEPRECIATION);

        if (!$depreciationAccount || !$accumulatedAccount) {
            throw new InvalidArgumentException('Стандардните сметки за амортизација не постојат во контниот план.');
        }

        $toRecord = [];

        foreach ($this->assets->allActive() as $asset) {
            if ($asset->purchaseDate > $periodDate) {
                continue; // средството сеуште не постоело во овој период
            }

            if ($this->assets->hasDepreciationForPeriod($asset->id, $periodDate)) {
                continue;
            }

            $accumulated = $this->assets->accumulatedDepreciation($asset->id);
            $remaining = bcsub($asset->purchaseValue, $accumulated, 2);

            if (bccomp($remaining, '0.00', 2) <= 0) {
                continue;
            }

            $monthly = $asset->monthlyDepreciation();
            $toRecord[$asset->id] = bccomp($monthly, $remaining, 2) > 0 ? $remaining : $monthly;
        }

        if (!$toRecord) {
            return ['count' => 0, 'total' => '0.00'];
        }

        $total = '0.00';
        foreach ($toRecord as $amount) {
            $total = bcadd($total, $amount, 2);
        }

        $entryId = $this->ledger->postEntry($periodDate, "Амортизација за период $periodDate", null, [
            ['account_id' => $depreciationAccount->id, 'debit' => $total, 'credit' => '0'],
            ['account_id' => $accumulatedAccount->id, 'debit' => '0', 'credit' => $total],
        ]);

        foreach ($toRecord as $assetId => $amount) {
            $this->assets->insertDepreciationEntry(new FixedAssetDepreciationEntry($assetId, $periodDate, $amount, $entryId));
        }

        return ['count' => count($toRecord), 'total' => $total];
    }
}
