import { Keyboard } from 'lucide-react';
import { MODULE_KEYBOARD_SHORTCUTS, POS_MODULE_LABELS, getAvailablePosModules } from '@/lib/moduleKeyboardShortcuts';

function ShortcutKeys({ keys }) {
    return (
        <span className="inline-flex flex-wrap items-center gap-1">
            {keys.split('+').map((part) => (
                <kbd
                    key={part}
                    className="inline-flex items-center rounded-md border border-slate-300 dark:border-slate-600 bg-slate-100 dark:bg-slate-800 px-2 py-1 font-mono text-[11px] font-semibold text-slate-700 dark:text-slate-200 shadow-sm"
                >
                    {part}
                </kbd>
            ))}
        </span>
    );
}

export default function ModuleKeyboardShortcutsCard({ modules = null, className = '' }) {
    const availableModules = Array.isArray(modules) && modules.length > 0 ? modules : getAvailablePosModules();
    const moduleLabel =
        availableModules.length > 0
            ? availableModules.map((key) => POS_MODULE_LABELS[key] || key).join(' · ')
            : 'Global Commerce · Quincaillerie';

    return (
        <section
            className={`rounded-2xl border border-indigo-200 dark:border-indigo-900/50 bg-gradient-to-br from-indigo-50/80 to-white dark:from-indigo-950/30 dark:to-slate-900 p-5 ${className}`}
        >
            <div className="flex items-center gap-2 mb-3">
                <span className="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-100 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-300">
                    <Keyboard className="h-4 w-4" />
                </span>
                <div>
                    <h2 className="font-semibold text-slate-900 dark:text-white">Raccourcis clavier POS</h2>
                    <p className="text-xs text-slate-600 dark:text-slate-300">
                        {moduleLabel} — maintenez <ShortcutKeys keys="Ctrl+Shift" /> puis la touche indiquée.
                    </p>
                </div>
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                {MODULE_KEYBOARD_SHORTCUTS.map((item) => (
                    <div
                        key={item.key}
                        className="flex items-center justify-between gap-3 rounded-xl border border-slate-200/80 dark:border-slate-700 bg-white/80 dark:bg-slate-900/70 px-3 py-2.5"
                    >
                        <span className="text-sm text-slate-700 dark:text-slate-200">{item.label}</span>
                        <ShortcutKeys keys={item.keys} />
                    </div>
                ))}
            </div>

            <p className="mt-3 text-xs text-slate-500 dark:text-slate-400">
                Sur l&apos;écran caisse, <ShortcutKeys keys="P" /> ouvre le paiement, <ShortcutKeys keys="+" /> /{' '}
                <ShortcutKeys keys="-" /> modifient la quantité. Les raccourcis ci-dessus fonctionnent hors champ de
                saisie.
            </p>
        </section>
    );
}
