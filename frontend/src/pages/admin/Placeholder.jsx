export default function Placeholder({ title, description, phase }) {
    return (
        <div className="page">
            <header className="page-head">
                <div>
                    <h2>{title}</h2>
                    <p>{description}</p>
                </div>
                {phase && <span className="pill pill-neutral">{phase}</span>}
            </header>

            <section className="card empty-state">
                <span className="empty-icon" aria-hidden="true">⚙</span>
                <h3>This screen is next in line</h3>
                <p>
                    The Laravel endpoint behind it already exists. Only the React screen still needs to
                    be built, which is what the next phases cover.
                </p>
            </section>
        </div>
    );
}
