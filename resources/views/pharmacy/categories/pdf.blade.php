<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des Catégories - Pharmacy</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10px;
            color: #333;
            line-height: 1.4;
        }
        
        .header {
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f59e0b;
        }
        
        .header h1 {
            font-size: 20px;
            color: #1f2937;
            margin-bottom: 5px;
        }
        
        .header-info {
            font-size: 9px;
            color: #6b7280;
            display: flex;
            justify-content: space-between;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        .table thead {
            background-color: #f59e0b;
            color: #fff;
        }
        
        .table th {
            padding: 8px;
            text-align: left;
            font-weight: bold;
            font-size: 9px;
            border: 1px solid #d97706;
        }
        
        .table td {
            padding: 6px 8px;
            border: 1px solid #e5e7eb;
            font-size: 9px;
        }
        
        .table tbody tr:nth-child(even) {
            background-color: #f9fafb;
        }
        
        .table tbody tr:hover {
            background-color: #fef3c7;
        }
        
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
        }
        
        .badge-active {
            background-color: #d1fae5;
            color: #065f46;
        }
        
        .badge-inactive {
            background-color: #fee2e2;
            color: #991b1b;
        }
        
        .footer {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #e5e7eb;
            font-size: 8px;
            color: #6b7280;
            text-align: center;
        }
        
        .summary {
            margin-top: 15px;
            padding: 10px;
            background-color: #fffbeb;
            border-left: 4px solid #f59e0b;
            font-size: 9px;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📋 Liste des Catégories - Pharmacy</h1>
        <div class="header-info">
            <div>
                <strong>Boutique:</strong> {{ $shop_name }}<br>
                <strong>Généré le:</strong> {{ $generated_at }}<br>
                <strong>Généré par:</strong> {{ $generated_by }}
            </div>
            <div class="text-right">
                <strong>Total:</strong> {{ $total }} catégorie(s)
            </div>
        </div>
    </div>
    
    @if(count($categories) > 0)
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 20%;">Nom</th>
                    <th style="width: 30%;">Description</th>
                    <th style="width: 15%;">Catégorie parente</th>
                    <th style="width: 8%;" class="text-center">Produits</th>
                    <th style="width: 8%;" class="text-center">Ordre</th>
                    <th style="width: 8%;" class="text-center">Statut</th>
                </tr>
            </thead>
            <tbody>
                @foreach($categories as $index => $category)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td><strong>{{ $category['name'] }}</strong></td>
                        <td>{{ $category['description'] }}</td>
                        <td>{{ $category['parent'] }}</td>
                        <td class="text-center">{{ $category['products_count'] }}</td>
                        <td class="text-center">{{ $category['sort_order'] }}</td>
                        <td class="text-center">
                            <span class="badge {{ $category['status'] === 'Active' ? 'badge-active' : 'badge-inactive' }}">
                                {{ $category['status'] }}
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        
        <div class="summary">
            <strong>Résumé:</strong> {{ $total }} catégorie(s) au total | 
            {{ collect($categories)->where('status', 'Active')->count() }} active(s) | 
            {{ collect($categories)->where('status', 'Inactive')->count() }} inactive(s)
        </div>
    @else
        <div style="text-align: center; padding: 40px; color: #6b7280;">
            <p style="font-size: 12px;">Aucune catégorie trouvée</p>
        </div>
    @endif
    
    <div class="footer">
        <p>Document généré automatiquement par le système POS SaaS - Pharmacy Module</p>
        <p>Page 1/1</p>
    </div>
</body>
</html>
