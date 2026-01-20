import { usePage } from '@inertiajs/react';
import { useEffect } from 'react';
import toast from 'react-hot-toast';

/**
 * Component: FlashMessages
 * 
 * Affiche les messages flash de Laravel via react-hot-toast
 */
export default function FlashMessages() {
    const { flash } = usePage().props;

    useEffect(() => {
        if (flash?.success) {
            toast.success(flash.success);
        }
        if (flash?.error) {
            toast.error(flash.error);
        }
        if (flash?.message) {
            toast.success(flash.message);
        }
    }, [flash]);

    return null;
}
