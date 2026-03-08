<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard ROOT - {{ $generated_at }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            color: #333;
        }
        .header p {
            margin: 5px 0;
            color: #666;
        }
        .kpis {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 30px;
        }
        .kpi-card {
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 5px;
            background-color: #f9f9f9;
        }
        .kpi-card h3 {
            margin: 0 0 10px 0;
            font-size: 14px;
            color: #666;
        }
        .kpi-card .value {
            font-size: 24px;
            font-weight: bold;
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Dashboard ROOT - Rapport Global</h1>
        <p>Généré le {{ $generated_at }}</p>
        @if($from && $to)
            <p>Période: {{ \Carbon\Carbon::parse($from)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($to)->format('d/m/Y') }}</p>
        @elseif($period)
            <p>Période: {{ $period }} derniers jours</p>
        @endif
    </div>

    <div class="kpis">
        <div class="kpi-card">
            <h3>Total Tenants</h3>
            <div class="value">{{ number_format($kpis['total_tenants'], 0, ',', ' ') }}</div>
        </div>
        <div class="kpi-card">
            <h3>Tenants Actifs</h3>
            <div class="value">{{ number_format($kpis['active_tenants'], 0, ',', ' ') }}</div>
        </div>
        <div class="kpi-card">
            <h3>Total Utilisateurs</h3>
            <div class="value">{{ number_format($kpis['total_users'], 0, ',', ' ') }}</div>
        </div>
        <div class="kpi-card">
            <h3>Utilisateurs Actifs</h3>
            <div class="value">{{ number_format($kpis['active_users'], 0, ',', ' ') }}</div>
        </div>
        <div class="kpi-card">
            <h3>Chiffre d'affaires</h3>
            <div class="value">{{ number_format($kpis['total_revenue'], 2, ',', ' ') }} FCFA</div>
        </div>
        <div class="kpi-card">
            <h3>Total Produits</h3>
            <div class="value">{{ number_format($kpis['total_products'] ?? 0, 0, ',', ' ') }}</div>
        </div>
    </div>

    <div class="footer">
        <p>Rapport généré automatiquement par le système POS SaaS</p>
    </div>
</body>
</html>
