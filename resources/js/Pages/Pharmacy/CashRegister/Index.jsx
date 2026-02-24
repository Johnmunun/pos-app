import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Badge } from '@/Components/ui/badge';
import { Wallet as CashRegisterIcon, Plus, LockOpen, Lock, ArrowLeft } from 'lucide-react';
import axios from 'axios';
import toast from 'react-hot-toast';

function Modal({ open, onClose, title, children }) {
    if (!open) return null;
    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50" onClick={onClose}>
            <div className="bg-white dark:bg-slate-900 rounded-xl shadow-xl max-w-md w-full mx-4 max-h-[90vh] overflow-y-auto" onClick={e => e.stopPropagation()}>
                <div className="p-4 border-b border-gray-200 dark:border-slate-700 flex items-center justify-between">
                    <h3 className="text-lg font-semibold text-gray-900 dark:text-white">{title}</h3>
                    <button type="button" onClick={onClose} className="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">×</button>
                </div>
                <div className="p-4">{children}</div>
            </div>
        </div>
    );
}

export default function CashRegisterIndex({ cashRegisters = [] }) {
    const [createOpen, setCreateOpen] = useState(false);
    const [openSessionOpen, setOpenSessionOpen] = useState(false);
    const [closeSessionOpen, setCloseSessionOpen] = useState(false);
    const [selectedRegister, setSelectedRegister] = useState(null);
    const [selectedSession, setSelectedSession] = useState(null);
    const [formCreate, setFormCreate] = useState({ name: '', code: '', description: '', initial_balance: '0' });
    const [formOpen, setFormOpen] = useState({ opening_balance: '0' });
    const [formClose, setFormClose] = useState({ closing_balance: '', notes: '' });
    const [submitting, setSubmitting] = useState(false);

    const handleCreateSubmit = async (e) => {
        e.preventDefault();
        setSubmitting(true);
        try {
            await axios.post(route('pharmacy.cash-registers.store'), {
                name: formCreate.name,
                code: formCreate.code,
                description: formCreate.description || null,
                initial_balance: formCreate.initial_balance || 0,
            });
            toast.success('Caisse créée');
            setCreateOpen(false);
            setFormCreate({ name: '', code: '', description: '', initial_balance: '0' });
            router.reload({ only: ['cashRegisters'] });
        } catch (err) {
            toast.error(err.response?.data?.message || 'Erreur');
        } finally {
            setSubmitting(false);
        }
    };

    const handleOpenSession = (reg) => {
        setSelectedRegister(reg);
        setFormOpen({ opening_balance: reg.initial_balance?.toString() ?? '0' });
        setOpenSessionOpen(true);
    };

    const handleOpenSessionSubmit = async (e) => {
        e.preventDefault();
        if (!selectedRegister) return;
        setSubmitting(true);
        try {
            await axios.post(route('pharmacy.cash-registers.open', selectedRegister.id), {
                opening_balance: parseFloat(formOpen.opening_balance) || 0,
            });
            toast.success('Session ouverte');
            setOpenSessionOpen(false);
            setSelectedRegister(null);
            router.reload({ only: ['cashRegisters'] });
        } catch (err) {
            toast.error(err.response?.data?.message || 'Erreur');
        } finally {
            setSubmitting(false);
        }
    };

    const handleCloseSession = (reg) => {
        const session = reg.open_session;
        if (!session) return;
        setSelectedRegister(reg);
        setSelectedSession(session);
        setFormClose({ closing_balance: '', notes: '' });
        setCloseSessionOpen(true);
    };

    const handleCloseSessionSubmit = async (e) => {
        e.preventDefault();
        if (!selectedSession) return;
        const closing = parseFloat(formClose.closing_balance);
        if (isNaN(closing) || closing < 0) {
            toast.error('Saisissez un solde de fermeture valide.');
            return;
        }
        setSubmitting(true);
        try {
            await axios.post(route('pharmacy.cash-registers.sessions.close', selectedSession.id), {
                closing_balance: closing,
                notes: formClose.notes || null,
            });
            toast.success('Session fermée');
            setCloseSessionOpen(false);
            setSelectedRegister(null);
            setSelectedSession(null);
            router.reload({ only: ['cashRegisters'] });
        } catch (err) {
            toast.error(err.response?.data?.message || 'Erreur');
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <AppLayout>
            <Head title="Caisses" />
            <div className="container mx-auto py-6 px-4">
                <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                    <div className="flex items-center gap-3">
                        <Link href={route('pharmacy.sales.index')} className="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
                            <ArrowLeft className="h-5 w-5" />
                        </Link>
                        <h1 className="text-2xl font-bold flex items-center gap-2 text-gray-900 dark:text-gray-100">
                            <CashRegisterIcon className="h-6 w-6" />
                            Caisses
                        </h1>
                    </div>
                    <Button onClick={() => setCreateOpen(true)} className="inline-flex items-center gap-2">
                        <Plus className="h-4 w-4" />
                        Nouvelle caisse
                    </Button>
                </div>

                <p className="text-gray-600 dark:text-gray-400 mb-6">
                    Ouvrez une session pour enregistrer les ventes sur une caisse. Fermez la session en fin de poste avec le solde réel.
                </p>

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    {cashRegisters.map((reg) => (
                        <div
                            key={reg.id}
                            className="bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700 p-4 shadow-sm"
                        >
                            <div className="flex items-start justify-between mb-2">
                                <div>
                                    <h3 className="font-semibold text-gray-900 dark:text-white">{reg.name}</h3>
                                    <p className="text-sm text-gray-500 dark:text-gray-400">{reg.code}</p>
                                </div>
                                {reg.open_session ? (
                                    <Badge className="bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300">Ouverte</Badge>
                                ) : (
                                    <Badge variant="secondary">Fermée</Badge>
                                )}
                            </div>
                            {reg.description && (
                                <p className="text-sm text-gray-600 dark:text-gray-400 mb-3 line-clamp-2">{reg.description}</p>
                            )}
                            {reg.open_session ? (
                                <>
                                    <p className="text-xs text-gray-500 dark:text-gray-400 mb-2">
                                        Ouverte le {reg.open_session.opened_at} · Solde d'ouverture: {Number(reg.open_session.opening_balance).toLocaleString()}
                                    </p>
                                    <Button variant="outline" size="sm" className="w-full border-amber-500 text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-900/20" onClick={() => handleCloseSession(reg)}>
                                        <Lock className="h-4 w-4 mr-2" />
                                        Fermer la session
                                    </Button>
                                </>
                            ) : (
                                <Button size="sm" className="w-full bg-green-600 hover:bg-green-700" onClick={() => handleOpenSession(reg)}>
                                    <LockOpen className="h-4 w-4 mr-2" />
                                    Ouvrir une session
                                </Button>
                            )}
                        </div>
                    ))}
                </div>

                {cashRegisters.length === 0 && (
                    <div className="text-center py-12 text-gray-500 dark:text-gray-400">
                        <CashRegisterIcon className="h-12 w-12 mx-auto mb-3 opacity-50" />
                        <p>Aucune caisse. Créez une caisse pour gérer les sessions.</p>
                        <Button className="mt-4" onClick={() => setCreateOpen(true)}>
                            <Plus className="h-4 w-4 mr-2" />
                            Nouvelle caisse
                        </Button>
                    </div>
                )}
            </div>

            <Modal open={createOpen} onClose={() => setCreateOpen(false)} title="Nouvelle caisse">
                <form onSubmit={handleCreateSubmit} className="space-y-4">
                    <div>
                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nom</label>
                        <Input value={formCreate.name} onChange={e => setFormCreate(prev => ({ ...prev, name: e.target.value }))} placeholder="Ex: Caisse 1" required />
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Code</label>
                        <Input value={formCreate.code} onChange={e => setFormCreate(prev => ({ ...prev, code: e.target.value }))} placeholder="Ex: CAISSE-01" required />
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description (optionnel)</label>
                        <Input value={formCreate.description} onChange={e => setFormCreate(prev => ({ ...prev, description: e.target.value }))} placeholder="Optionnel" />
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Fonds de caisse initial (optionnel)</label>
                        <Input type="number" step="0.01" min="0" value={formCreate.initial_balance} onChange={e => setFormCreate(prev => ({ ...prev, initial_balance: e.target.value }))} />
                    </div>
                    <div className="flex gap-2 pt-2">
                        <Button type="button" variant="outline" onClick={() => setCreateOpen(false)} className="flex-1">Annuler</Button>
                        <Button type="submit" disabled={submitting} className="flex-1">Créer</Button>
                    </div>
                </form>
            </Modal>

            <Modal open={openSessionOpen} onClose={() => { setOpenSessionOpen(false); setSelectedRegister(null); }} title="Ouvrir une session">
                {selectedRegister && (
                    <form onSubmit={handleOpenSessionSubmit} className="space-y-4">
                        <p className="text-sm text-gray-600 dark:text-gray-400">Caisse: <strong>{selectedRegister.name}</strong></p>
                        <div>
                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Solde d'ouverture</label>
                            <Input type="number" step="0.01" min="0" value={formOpen.opening_balance} onChange={e => setFormOpen(prev => ({ ...prev, opening_balance: e.target.value }))} />
                        </div>
                        <div className="flex gap-2 pt-2">
                            <Button type="button" variant="outline" onClick={() => setOpenSessionOpen(false)} className="flex-1">Annuler</Button>
                            <Button type="submit" disabled={submitting} className="flex-1">Ouvrir</Button>
                        </div>
                    </form>
                )}
            </Modal>

            <Modal open={closeSessionOpen} onClose={() => { setCloseSessionOpen(false); setSelectedRegister(null); setSelectedSession(null); }} title="Fermer la session">
                {selectedRegister && selectedSession && (
                    <form onSubmit={handleCloseSessionSubmit} className="space-y-4">
                        <p className="text-sm text-gray-600 dark:text-gray-400">Caisse: <strong>{selectedRegister.name}</strong>. Saisissez le solde réel en caisse.</p>
                        <div>
                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Solde de fermeture (réel) *</label>
                            <Input type="number" step="0.01" min="0" value={formClose.closing_balance} onChange={e => setFormClose(prev => ({ ...prev, closing_balance: e.target.value }))} placeholder="0.00" required />
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notes (optionnel)</label>
                            <Input value={formClose.notes} onChange={e => setFormClose(prev => ({ ...prev, notes: e.target.value }))} placeholder="Ex: Écart expliqué..." />
                        </div>
                        <div className="flex gap-2 pt-2">
                            <Button type="button" variant="outline" onClick={() => setCloseSessionOpen(false)} className="flex-1">Annuler</Button>
                            <Button type="submit" disabled={submitting} className="flex-1">Fermer la session</Button>
                        </div>
                    </form>
                )}
            </Modal>
        </AppLayout>
    );
}
