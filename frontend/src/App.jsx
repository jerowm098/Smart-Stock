import { Route, Routes } from 'react-router-dom';
import { ProtectedRoute, RequireRole } from './components/Guards.jsx';
import { PageLoadbarProvider } from './components/PageLoadbar.jsx';
import AppLayout from './layouts/AppLayout.jsx';
import Home from './pages/Home.jsx';
import Login from './pages/Login.jsx';
import ForgotPassword from './pages/ForgotPassword.jsx';
import ResetPassword from './pages/ResetPassword.jsx';
import Products from './pages/admin/Products.jsx';
import Categories from './pages/admin/Categories.jsx';
import StockIn from './pages/admin/StockIn.jsx';
import Dashboard from './pages/admin/Dashboard.jsx';
import Transactions from './pages/admin/Transactions.jsx';
import AuditLogs from './pages/admin/AuditLogs.jsx';
import OrderSuggestions from './pages/admin/OrderSuggestions.jsx';
import Suppliers from './pages/admin/Suppliers.jsx';
import Users from './pages/admin/Users.jsx';
import Backups from './pages/admin/Backups.jsx';
import AdminPos from './pages/admin/Pos.jsx';
import Pos from './pages/cashier/Pos.jsx';
import NotFound from './pages/NotFound.jsx';

export default function App() {
    return (
        <PageLoadbarProvider>
            <Routes>
                <Route path="/" element={<Home />} />
                <Route path="/login" element={<Login />} />
                <Route path="/forgot-password" element={<ForgotPassword />} />
                <Route path="/reset-password" element={<ResetPassword />} />

                <Route element={<ProtectedRoute />}>
                    <Route element={<AppLayout />}>
                        <Route element={<RequireRole role="admin" />}>
                            <Route path="/dashboard" element={<Dashboard />} />
                            <Route path="/products" element={<Products />} />
                            <Route path="/categories" element={<Categories />} />
                            <Route path="/stock-in" element={<StockIn />} />
                            <Route path="/checkout" element={<AdminPos />} />
                            <Route path="/transactions" element={<Transactions />} />
                            <Route path="/audit-logs" element={<AuditLogs />} />
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
        </PageLoadbarProvider>
    );
}

