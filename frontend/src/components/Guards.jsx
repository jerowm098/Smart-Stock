import { Navigate, Outlet, useLocation } from 'react-router-dom';
import { homeFor } from '../lib/api.js';
import { useAuth } from '../auth/AuthContext.jsx';

export function FullPageLoader({ label = 'Loading…' }) {
    return (
        <div className="full-loader">
            <span className="spinner" aria-hidden="true" />
            <p>{label}</p>
        </div>
    );
}

/** Any signed-in user may pass. */
export function ProtectedRoute() {
    const { user, loading } = useAuth();
    const location = useLocation();

    if (loading) {
        return <FullPageLoader label="Checking session…" />;
    }

    if (!user) {
        return <Navigate to="/login" replace state={{ from: location }} />;
    }

    return <Outlet />;
}

/** Only the given role may pass; everyone else lands on their own home. */
export function RequireRole({ role }) {
    const { user, loading } = useAuth();

    if (loading) {
        return <FullPageLoader label="Checking session…" />;
    }

    if (!user) {
        return <Navigate to="/login" replace />;
    }

    if (user.role !== role) {
        return <Navigate to={homeFor(user)} replace />;
    }

    return <Outlet />;
}
