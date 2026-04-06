/**
 * Panier e-commerce : produits numériques ou physiques « paiement immédiat »
 * → le front ne propose que les méthodes de type paiement en ligne immédiat (ex. intégration technique fusionpay côté serveur).
 */
export function cartRequiresFusionPay(cart) {
    if (!Array.isArray(cart) || cart.length === 0) {
        return false;
    }
    return cart.some((item) => {
        if (item.is_digital) {
            return true;
        }
        const mode = item.mode_paiement || 'paiement_immediat';
        return mode !== 'paiement_livraison';
    });
}

export function paymentMethodsForCart(paymentMethods, cart) {
    if (!Array.isArray(paymentMethods)) {
        return [];
    }
    if (!cartRequiresFusionPay(cart)) {
        return paymentMethods;
    }
    return paymentMethods.filter((m) => String(m.type || '').toLowerCase() === 'fusionpay');
}
