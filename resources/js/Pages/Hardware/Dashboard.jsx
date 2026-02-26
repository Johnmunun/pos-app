import React from 'react';
import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Package, AlertTriangle, Layers, TrendingUp } from 'lucide-react';

export default function HardwareDashboard({ stats = {}, filters = {} }) {
  const productsTotal = stats.products_total ?? 0;
  const lowStock = stats.low_stock_count ?? 0;
  const outOfStock = stats.out_of_stock_count ?? 0;

  return (
    <AppLayout
      header={
        <h2 className="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
          Tableau de bord — Quincaillerie
        </h2>
      }
    >
      <Head title="Dashboard Quincaillerie" />

      <div className="py-6 space-y-6">
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          <Card className="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700">
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium text-gray-700 dark:text-gray-200">
                Produits
              </CardTitle>
              <Package className="h-4 w-4 text-gray-400 dark:text-gray-500" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold text-gray-900 dark:text-white">
                {productsTotal}
              </div>
              <p className="text-xs text-gray-500 dark:text-gray-400">
                Articles de quincaillerie enregistrés
              </p>
            </CardContent>
          </Card>

          <Card className="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700">
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium text-gray-700 dark:text-gray-200">
                Stock bas
              </CardTitle>
              <AlertTriangle className="h-4 w-4 text-orange-500" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold text-orange-600 dark:text-orange-400">
                {lowStock}
              </div>
              <p className="text-xs text-gray-500 dark:text-gray-400">
                Références à surveiller
              </p>
            </CardContent>
          </Card>

          <Card className="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700">
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium text-gray-700 dark:text-gray-200">
                Ruptures
              </CardTitle>
              <Layers className="h-4 w-4 text-red-500" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold text-red-600 dark:text-red-400">
                {outOfStock}
              </div>
              <p className="text-xs text-gray-500 dark:text-gray-400">
                Produits à réapprovisionner
              </p>
            </CardContent>
          </Card>

          <Card className="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700">
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium text-gray-700 dark:text-gray-200">
                Vue globale
              </CardTitle>
              <TrendingUp className="h-4 w-4 text-emerald-500" />
            </CardHeader>
            <CardContent>
              <p className="text-xs text-gray-500 dark:text-gray-400">
                Les ventes et statistiques détaillées seront ajoutées ici au fur et à mesure de
                l&apos;implémentation du module Quincaillerie.
              </p>
            </CardContent>
          </Card>
        </div>

        <Card className="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700">
          <CardHeader>
            <CardTitle className="text-base text-gray-900 dark:text-white">
              Bienvenue dans le module Quincaillerie
            </CardTitle>
          </CardHeader>
          <CardContent className="space-y-2 text-sm text-gray-600 dark:text-gray-300">
            <p>
              Ce module sera progressivement enrichi avec les mêmes fonctionnalités que la
              pharmacie&nbsp;: gestion des produits (ciment, fer à béton, clous, peinture, etc.),
              ventes, mouvements de stock et rapports.
            </p>
            <p>
              L&apos;interface reste 100&nbsp;% responsive et compatible avec le mode clair / sombre.
            </p>
          </CardContent>
        </Card>
      </div>
    </AppLayout>
  );
}

