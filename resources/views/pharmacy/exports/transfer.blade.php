@extends('pharmacy.exports.base')

@section('title', 'Bon de Transfert Inter-Magasin')
@section('subtitle', 'Réf: ' . $transfer['reference'])

@section('content')
    {{-- Statut du transfert --}}
    <div style="text-align: right; margin-bottom: 16px;">
        @php
            $statusClass = match($transfer['status']) {
                'draft' => 'badge-warning',
                'validated' => 'badge-success',
                'cancelled' => 'badge-danger',
                default => 'badge-neutral'
            };
        @endphp
        <span class="badge {{ $statusClass }}" style="font-size: 10px; padding: 4px 12px;">
            {{ $transfer['status_label'] }}
        </span>
    </div>

    {{-- Informations des magasins --}}
    <div style="display: table; width: 100%; margin-bottom: 16px;">
        {{-- Magasin Source --}}
        <div style="display: table-cell; width: 48%; vertical-align: top;">
            <div class="summary-box">
                <div class="summary-title" style="color: #dc2626;">📤 Magasin Source (Expéditeur)</div>
                <div style="padding: 8px 0;">
                    <div style="font-weight: bold; font-size: 11px; color: #1e293b;">
                        {{ $transfer['from_shop_name'] }}
                    </div>
                    @if($transfer['from_shop_address'])
                        <div style="font-size: 9px; color: #64748b; margin-top: 4px;">
                            {{ $transfer['from_shop_address'] }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
        
        {{-- Flèche --}}
        <div style="display: table-cell; width: 4%; text-align: center; vertical-align: middle;">
            <span style="font-size: 20px; color: #3b82f6;">→</span>
        </div>
        
        {{-- Magasin Destination --}}
        <div style="display: table-cell; width: 48%; vertical-align: top;">
            <div class="summary-box">
                <div class="summary-title" style="color: #16a34a;">📥 Magasin Destination (Récepteur)</div>
                <div style="padding: 8px 0;">
                    <div style="font-weight: bold; font-size: 11px; color: #1e293b;">
                        {{ $transfer['to_shop_name'] }}
                    </div>
                    @if($transfer['to_shop_address'])
                        <div style="font-size: 9px; color: #64748b; margin-top: 4px;">
                            {{ $transfer['to_shop_address'] }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Tableau des produits --}}
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 10%;">#</th>
                <th style="width: 20%;">Code</th>
                <th style="width: 50%;">Désignation</th>
                <th class="align-right" style="width: 20%;">Quantité</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $index => $item)
                <tr>
                    <td class="align-center">{{ $index + 1 }}</td>
                    <td class="nowrap font-bold">{{ $item['product_code'] ?: '—' }}</td>
                    <td>{{ $item['product_name'] }}</td>
                    <td class="align-right font-bold">{{ $item['quantity'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="align-center" style="padding: 20px; color: #64748b;">
                        Aucun produit dans ce transfert
                    </td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr style="background: #f1f5f9;">
                <td colspan="3" class="align-right font-bold" style="padding: 8px;">
                    TOTAL
                </td>
                <td class="align-right font-bold" style="padding: 8px; font-size: 11px;">
                    {{ $transfer['total_quantity'] }} unités
                </td>
            </tr>
        </tfoot>
    </table>

    {{-- Informations supplémentaires --}}
    <div class="summary-box" style="margin-top: 16px;">
        <div class="summary-title">Informations</div>
        <div style="display: table; width: 100%;">
            <div style="display: table-cell; width: 50%; padding-right: 10px;">
                <div class="summary-row">
                    <span class="summary-label">Nombre de produits</span>
                    <span class="summary-value">{{ $transfer['total_items'] }}</span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Créé par</span>
                    <span class="summary-value">{{ $transfer['created_by_name'] }}</span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Date de création</span>
                    <span class="summary-value">{{ $transfer['created_at'] }}</span>
                </div>
            </div>
            <div style="display: table-cell; width: 50%;">
                @if($transfer['validated_by_name'])
                    <div class="summary-row">
                        <span class="summary-label">Validé par</span>
                        <span class="summary-value">{{ $transfer['validated_by_name'] }}</span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">Date de validation</span>
                        <span class="summary-value">{{ $transfer['validated_at'] }}</span>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Notes --}}
    @if($transfer['notes'])
        <div style="margin-top: 12px; padding: 10px; background: #fffbeb; border: 1px solid #fcd34d; border-radius: 4px;">
            <div style="font-weight: bold; font-size: 9px; color: #92400e; margin-bottom: 4px;">Notes :</div>
            <div style="font-size: 9px; color: #78350f;">{{ $transfer['notes'] }}</div>
        </div>
    @endif

    {{-- Signatures --}}
    <div style="margin-top: 40px; display: table; width: 100%;">
        <div style="display: table-cell; width: 33%; text-align: center;">
            <div style="border-top: 1px solid #333; width: 180px; margin: 50px auto 0; padding-top: 5px;">
                Responsable Stock<br>(Expéditeur)
            </div>
        </div>
        <div style="display: table-cell; width: 34%; text-align: center;">
            <div style="border-top: 1px solid #333; width: 180px; margin: 50px auto 0; padding-top: 5px;">
                Transporteur
            </div>
        </div>
        <div style="display: table-cell; width: 33%; text-align: center;">
            <div style="border-top: 1px solid #333; width: 180px; margin: 50px auto 0; padding-top: 5px;">
                Responsable Stock<br>(Récepteur)
            </div>
        </div>
    </div>

    {{-- Footer note --}}
    <div style="margin-top: 30px; padding: 10px; background: #f8f9fa; border-radius: 4px; font-size: 8px; color: #666;">
        <strong>Important :</strong> Ce document doit accompagner les marchandises lors du transfert.
        Les quantités doivent être vérifiées à la réception.
        Tout écart doit être signalé immédiatement.
        <br><br>
        Document généré le {{ now()->format('d/m/Y à H:i') }}.
    </div>
@endsection
