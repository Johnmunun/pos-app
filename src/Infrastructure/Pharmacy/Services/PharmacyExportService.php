<?php

declare(strict_types=1);

namespace Src\Infrastructure\Pharmacy\Services;

use App\Models\User as UserModel;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Src\Application\Settings\UseCases\GetStoreSettingsUseCase;
use Src\Infrastructure\Settings\Services\StoreLogoService;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Service centralisé pour les exports PDF et Excel du module Pharmacy.
 * 
 * Génère des documents professionnels avec en-têtes entreprise,
 * respectant l'isolation multi-tenant.
 */
class PharmacyExportService
{
    public function __construct(
        private readonly GetStoreSettingsUseCase $getStoreSettingsUseCase,
        private readonly StoreLogoService $storeLogoService
    ) {
    }

    /**
     * Récupère les informations d'en-tête pour les exports.
     * 
     * @param Request $request
     * @return array{
     *   shop_id: string|null,
     *   is_root: bool,
     *   company_name: string,
     *   id_nat: string|null,
     *   rccm: string|null,
     *   tax_number: string|null,
     *   street: string|null,
     *   city: string|null,
     *   postal_code: string|null,
     *   country: string|null,
     *   phone: string|null,
     *   email: string|null,
     *   logo_url: string|null,
     *   currency: string,
     *   exported_at: \Carbon\Carbon,
     *   exported_by: string
     * }
     */
    public function getExportHeader(Request $request): array
    {
        $user = $request->user();
        if ($user === null) {
            abort(403, 'User not authenticated.');
        }
        $shopId = $user->shop_id ?? ($user->tenant_id ? (string) $user->tenant_id : null);
        
        /** @var UserModel|null $userModel */
        $userModel = UserModel::query()->find($user->id);
        $isRoot = $userModel ? $userModel->isRoot() : false;

        if (!$shopId && !$isRoot) {
            abort(403, 'Shop ID not found. Please contact administrator.');
        }

        $settings = null;
        $currency = 'CDF';
        
        if ($shopId) {
            $settings = $this->getStoreSettingsUseCase->execute((string) $shopId);
            
            // Récupérer la devise de la boutique
            if ($user->shop && isset($user->shop->currency)) {
                $currency = $user->shop->currency;
            }
        }

        return [
            'shop_id' => $shopId,
            'is_root' => $isRoot,
            'company_name' => $settings ? $settings->getCompanyIdentity()->getName() : ($user->shop->name ?? 'Pharmacie'),
            'id_nat' => $settings ? $settings->getCompanyIdentity()->getIdNat() : null,
            'rccm' => $settings ? $settings->getCompanyIdentity()->getRccm() : null,
            'tax_number' => $settings ? $settings->getCompanyIdentity()->getTaxNumber() : null,
            'street' => $settings ? $settings->getAddress()->getStreet() : null,
            'city' => $settings ? $settings->getAddress()->getCity() : null,
            'postal_code' => $settings ? $settings->getAddress()->getPostalCode() : null,
            'country' => $settings ? $settings->getAddress()->getCountry() : null,
            'phone' => $settings ? $settings->getPhone() : null,
            'email' => $settings ? $settings->getEmail() : null,
            'logo_url' => $settings && $settings->getLogoPath()
                ? $this->storeLogoService->getUrl($settings->getLogoPath())
                : null,
            'currency' => $currency,
            'exported_at' => now(),
            'exported_by' => $user->name ?? $user->email ?? 'Utilisateur',
        ];
    }

    /**
     * Génère un export PDF.
     * 
     * @param string $view Le nom de la vue Blade
     * @param array<string, mixed> $data Les données pour la vue
     * @param string $filename Le nom du fichier (sans extension)
     * @param string $orientation 'portrait' ou 'landscape'
     * @return Response
     */
    public function exportPdf(string $view, array $data, string $filename, string $orientation = 'portrait'): Response
    {
        $pdf = Pdf::loadView($view, $data);
        $pdf->setPaper('a4', $orientation);

        $fullFilename = $filename . '_' . now()->format('Ymd_His') . '.pdf';
        return $pdf->download($fullFilename);
    }

    /**
     * Génère un export Excel avec en-tête entreprise.
     * 
     * @param array<string, mixed> $header Les informations d'en-tête
     * @param string $title Le titre du document
     * @param array<int, string> $columns Les en-têtes de colonnes
     * @param array<int, array<int, mixed>> $rows Les données
     * @param string $filename Le nom du fichier (sans extension)
     * @param array<string, string> $columnAlignments Alignements par colonne (ex: ['E' => 'right'])
     * @return StreamedResponse
     */
    public function exportExcel(
        array $header,
        string $title,
        array $columns,
        array $rows,
        string $filename,
        array $columnAlignments = []
    ): StreamedResponse {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(mb_substr($title, 0, 31)); // Excel limite à 31 caractères

        $row = 1;
        $lastCol = Coordinate::stringFromColumnIndex(count($columns));

        // === En-tête entreprise ===
        $sheet->setCellValue('A' . $row, 'Entreprise');
        $sheet->setCellValue('B' . $row, $header['company_name'] ?? '');
        $sheet->getStyle('B' . $row)->getFont()->setBold(true)->setSize(12);
        $row++;

        // Adresse
        $addressParts = array_filter([
            $header['street'] ?? null,
            $header['city'] ?? null,
            $header['postal_code'] ?? null,
            $header['country'] ?? null,
        ]);
        if (!empty($addressParts)) {
            $sheet->setCellValue('A' . $row, 'Adresse');
            $sheet->setCellValue('B' . $row, implode(', ', $addressParts));
            $row++;
        }

        // Contact
        $contact = trim(
            ($header['phone'] ? 'Tél: ' . $header['phone'] . ' ' : '') . 
            ($header['email'] ? 'Email: ' . $header['email'] : '')
        );
        if (!empty($contact)) {
            $sheet->setCellValue('A' . $row, 'Contact');
            $sheet->setCellValue('B' . $row, $contact);
            $row++;
        }

        // Identifiants légaux
        if (!empty($header['id_nat']) || !empty($header['rccm']) || !empty($header['tax_number'])) {
            $legal = array_filter([
                $header['id_nat'] ? 'ID NAT: ' . $header['id_nat'] : null,
                $header['rccm'] ? 'RCCM: ' . $header['rccm'] : null,
                $header['tax_number'] ? 'N° Tva: ' . $header['tax_number'] : null,
            ]);
            $sheet->setCellValue('A' . $row, 'Identification');
            $sheet->setCellValue('B' . $row, implode(' · ', $legal));
            $row++;
        }

        // Date d'export et utilisateur
        $sheet->setCellValue('A' . $row, 'Date d\'export');
        $exportedAt = $header['exported_at'] ?? now();
        $sheet->setCellValue('B' . $row, $exportedAt->format('d/m/Y H:i'));
        $row++;

        if (!empty($header['exported_by'])) {
            $sheet->setCellValue('A' . $row, 'Exporté par');
            $sheet->setCellValue('B' . $row, $header['exported_by']);
            $row++;
        }

        $row++; // Ligne vide

        // === Titre du document ===
        $sheet->setCellValue('A' . $row, $title);
        $sheet->mergeCells('A' . $row . ':' . $lastCol . $row);
        $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $row++;
        $row++; // Ligne vide

        // === En-têtes des colonnes ===
        $headerRow = $row;
        foreach ($columns as $col => $label) {
            $colLetter = Coordinate::stringFromColumnIndex($col + 1);
            $sheet->setCellValue($colLetter . $row, $label);
        }
        $row++;

        // Style des en-têtes
        $headerRange = 'A' . $headerRow . ':' . $lastCol . $headerRow;
        $sheet->getStyle($headerRange)->getFont()->setBold(true);
        $sheet->getStyle($headerRange)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('1E40AF'); // Bleu foncé
        $sheet->getStyle($headerRange)->getFont()->getColor()->setRGB('FFFFFF');
        $sheet->getStyle($headerRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // === Données ===
        $dataStartRow = $row;
        $isEven = false;
        foreach ($rows as $rowData) {
            foreach ($rowData as $col => $value) {
                $colLetter = Coordinate::stringFromColumnIndex($col + 1);
                $sheet->setCellValue($colLetter . $row, $value);
            }
            
            // Lignes alternées (zébrées)
            if ($isEven) {
                $sheet->getStyle('A' . $row . ':' . $lastCol . $row)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('F8FAFC');
            }
            $isEven = !$isEven;
            $row++;
        }
        $dataEndRow = $row - 1;

        // === Alignements ===
        if ($dataEndRow >= $dataStartRow) {
            foreach ($columnAlignments as $col => $alignment) {
                $alignType = match ($alignment) {
                    'right' => Alignment::HORIZONTAL_RIGHT,
                    'center' => Alignment::HORIZONTAL_CENTER,
                    default => Alignment::HORIZONTAL_LEFT,
                };
                $sheet->getStyle($col . $dataStartRow . ':' . $col . $dataEndRow)
                    ->getAlignment()->setHorizontal($alignType);
            }
        }

        // === Bordures ===
        if ($dataEndRow >= $headerRow) {
            $dataRange = 'A' . $headerRow . ':' . $lastCol . $dataEndRow;
            $sheet->getStyle($dataRange)->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN)
                ->getColor()->setRGB('E2E8F0');
        }

        // === Auto-size colonnes ===
        $colIndex = 1;
        while ($colIndex <= count($columns)) {
            $colLetter = Coordinate::stringFromColumnIndex($colIndex);
            $sheet->getColumnDimension($colLetter)->setAutoSize(true);
            $colIndex++;
        }

        $fullFilename = $filename . '_' . now()->format('Ymd_His') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $fullFilename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Formate un montant avec devise.
     * 
     * @param float|int|null $amount
     * @param string $currency
     * @return string
     */
    public function formatCurrency(float|int|null $amount, string $currency = 'CDF'): string
    {
        if ($amount === null) {
            return '—';
        }
        return number_format((float) $amount, 2, ',', ' ') . ' ' . $currency;
    }

    /**
     * Formate une date pour l'affichage.
     * 
     * @param mixed $date
     * @param string $format
     * @return string
     */
    public function formatDate(mixed $date, string $format = 'd/m/Y'): string
    {
        if ($date === null) {
            return '—';
        }
        
        if ($date instanceof \DateTimeInterface) {
            return $date->format($format);
        }
        
        if (is_string($date)) {
            try {
                return (new \DateTime($date))->format($format);
            } catch (\Exception $e) {
                return $date;
            }
        }
        
        return '—';
    }
}
