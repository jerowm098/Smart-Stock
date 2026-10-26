import { Route, Routes } from 'react-router-dom';
import { ProtectedRoute, RequireRole } from './components/Guards.jsx';
import AppLayout from './layouts/AppLayout.jsx';
import Home from './pages/Home.jsx';
import Login from './pages/Login.jsx';
import Products from './pages/admin/Products.jsx';
import Placeholder from './pages/admin/Placeholder.jsx';
import Pos from './pages/cashier/Pos.jsx';
import NotFound from './pages/NotFound.jsx';

const ADMIN_SCREENS = [
    {
        path: '/stock-in',
        title: 'Stock-In',
        description: 'Receive deliveries from suppliers and update on-hand quantities.',
        phase: 'Phase 2',
    },
    {
        path: '/transactions',
        title: 'Transactions',
        description: 'Browse the sales history and export transaction summaries.',
        phase: 'Phase 2',
    },
    {
        path: '/order-suggestions',
        title: 'Order Suggestions',
        description: 'Demand-based restocking suggestions generated from 30 days of sales.',
        phase: 'Phase 2',
    },
    {
        path: '/suppliers',
        title: 'Suppliers',
        description: 'Directory of active suppliers used by the receiving screen.',
        phase: 'Phase 2',
    },
    {
        path: '/users',
        title: 'Users',
        description: 'Create accounts, assign roles and reset passwords.',
        phase: 'Phase 2',
    },
    {
        path: '/backups',
        title: 'Backups',
        description: 'Run and download database backups.',
        phase: 'Phase 2',
    },
];

export default function App() {
    return (
        <Routes>
            <Route path="/" element={<Home />} />
            <Route path="/login" element={<Login />} />

            <Route element={<ProtectedRoute />}>
                <Route element={<AppLayout />}>
                    <Route element={<RequireRole role="admin" />}>
                        <Route path="/products" element={<Products />} />

                        {ADMIN_SCREENS.map((screen) => (
                            <Route
                                key={screen.path}
                                path={screen.path}
                                element={<Placeholder {...screen} />}
                            />
                        ))}
                    </Route>

                    <Route element={<RequireRole role="cashier" />}>
                        <Route path="/pos" element={<Pos />} />
                    </Route>
                </Route>
            </Route>

            <Route path="*" element={<NotFound />} />
        </Routes>
    );
}
