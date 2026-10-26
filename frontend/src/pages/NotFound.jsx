import { Link } from 'react-router-dom';

export default function NotFound() {
    return (
        <div className="notfound">
            <span className="eyebrow">404</span>
            <h1>Page not found</h1>
            <p>The screen you asked for is not part of this build.</p>
            <Link to="/" className="btn btn-primary">
                Back to home
            </Link>
        </div>
    );
}
