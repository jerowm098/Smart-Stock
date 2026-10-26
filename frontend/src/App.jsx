import { Route, Routes } from 'react-router-dom';
import { ProtectedRoute, RequireRole } from './components/Guards.jsx';
import AppLayout from './layouts/AppLayout.jsx';
import Home from './pages/Home.jsx';
import Login from './pages/Login.jsx';
import Products from './pages/admin/Products.jsx';
import StockIn from './pages/admin/StockIn.jsx';
import Transactions from './pages/admin/Transactions.jsx';
import OrderSuggestions from './pages/admin/OrderSuggestions.jsx';
import Suppliers from './pages/admin/Suppliers.jsx';
import Users from './pages/admin/Users.jsx';
import Backups from './pages/admin/Backups.jsx';
import Pos from './pages/cashier/Pos.jsx';
import NotFound from './pages/NotFound.jsx';

export default function App() {
    return (
        <Routes>
            <Route path="/" element={<Home />} />
            <Route path="/login" element={<Login />} />

            <Route element={<ProtectedRoute />}>
                <Route element={<AppLayout />}>
                    <Route element={<RequireRole role="admin" />}>
                        <Route path="/products" element={<Products />} />
                        <Route path="/stock-in" element={<StockIn />} />
                        <Route path="/transactions" element={<Transactions />} />
                        <Route path="/order-suggestions" element={<OrderSuggestions />} />
                        <Route path="/suppliers" element={<Suppliers />} />
                        <Route path="/users" element={<Users />} />
                        <Route path="/backups" element={<Backups />} />
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

