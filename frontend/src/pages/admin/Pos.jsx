import PosTerminal from '../../components/PosTerminal.jsx';

/**
 * Admin dashboard till.
 *
 * Same selling UI as the cashier's POS, but every sale created here is stamped
 * with channel = 'admin' by the backend, so the Transactions page and CSV
 * export can separate admin transactions from cashier transactions.
 */
export default function AdminPos() {
    return <PosTerminal channel="admin" />;
}
