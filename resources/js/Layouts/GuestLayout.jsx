/**
 * Layout: GuestLayout
 * 
 * Layout pour les pages publiques (login, register)
 * Support dark mode
 */
export default function GuestLayout({ children }) {
    // Le layout est maintenant géré directement dans les pages Login/Register
    // pour un meilleur contrôle du design
    return <>{children}</>;
}
